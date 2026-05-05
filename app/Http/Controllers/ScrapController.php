<?php

namespace App\Http\Controllers;

use App\Services\ScrapService;
use App\Traits\Geocode;
use App\Traits\SendEmails;
use Carbon\Carbon;
use Exception;
use Horsefly\Contact;
use Horsefly\EmailTemplate;
use Horsefly\JobCategory;
use Horsefly\JobSource;
use Horsefly\JobTitle;
use Horsefly\ModuleNote;
use Horsefly\Office;
use Horsefly\Sale;
use Horsefly\Setting;
use Horsefly\Unit;
use Horsefly\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Http\Client\ConnectionException;
use Throwable;

class ScrapController extends Controller
{
    use Geocode, SendEmails;

    public function importIndex()
    {
        return response()->json([
            'status' => 'ok',
            'message' => 'Scrap import endpoint is available. POST to ' . route('scrap.import') . ' with actor_key and optional input.',
        ]);
    }

    /**
     * Fetch jobs from a scraper actor stored in DB settings and import them.
     *
     * POST body (JSON or form):
     *   actor_key  string  required  e.g. "scrap_apify_indeed"
     *   input      array   optional  extra payload forwarded to the actor
     */
    public function importJobs(Request $request)
    {
        $actorKey = $request->input('actor_key');

        if (empty($actorKey)) {
            return response()->json([
                'success' => false,
                'message' => 'actor_key is required.',
            ], 422);
        }

        // Allow this import action to run longer than PHP's default request limit.
        // The actual HTTP requests inside the import are still bounded by per-request timeouts.
        if (function_exists('set_time_limit')) {
            set_time_limit(0);
        }

        $input = $request->input('input', []);
        if (is_string($input)) {
            $input = json_decode($input, true) ?: [];
        }

        try {
            // ---------------------------------------------------------------
            // 1. FETCH JOBS from the scraper API via ScrapService
            // ---------------------------------------------------------------
            $service = new ScrapService;
            $jobs = $service->runByKey($actorKey, $input);

            if (empty($jobs) || ! is_array($jobs)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No jobs returned from scraper API.',
                ], 400);
            }

            // ---------------------------------------------------------------
            // 2. PROCESS JOBS IN MANAGEABLE CHUNKS TO AVOID TIMEOUT
            // ---------------------------------------------------------------
            $totalCount = count($jobs);
            $chunkSize = 25; // Smaller chunks to prevent timeout
            $jobChunks = array_chunk($jobs, $chunkSize);
            $importedCount = 0;
            $failedChunks = [];

            foreach ($jobChunks as $chunkIndex => $jobChunk) {
                try {
                    $result = match (true) {
                        str_contains($actorKey, 'scrap_apify_indeed') => $this->persistJobsIndeed($jobChunk),
                        str_contains($actorKey, 'scrap_apify_totaljobs') => $this->persistJobsTotalJob($jobChunk),
                        str_contains($actorKey, 'scrap_apify_reed') => $this->persistJobsReed($jobChunk),
                        default => throw new InvalidArgumentException("No persist handler found for actor_key: [{$actorKey}]"),
                    };

                    $importedCount += $result;

                    Log::info('[ScrapImport] Chunk processed', [
                        'actor_key' => $actorKey,
                        'chunk_index' => $chunkIndex + 1,
                        'total_chunks' => count($jobChunks),
                        'chunk_size' => count($jobChunk),
                        'imported' => $result,
                    ]);
                } catch (ConnectionException $e) {
                    // Handle timeout/connection errors
                    $failedChunks[] = [
                        'chunk' => $chunkIndex + 1,
                        'error' => 'Timeout/Connection Error: ' . $e->getMessage(),
                    ];

                    Log::warning('[ScrapImport] Chunk timeout/connection error', [
                        'actor_key' => $actorKey,
                        'chunk_index' => $chunkIndex + 1,
                        'error' => $e->getMessage(),
                    ]);
                } catch (Throwable $e) {
                    // Handle other errors
                    $failedChunks[] = [
                        'chunk' => $chunkIndex + 1,
                        'error' => $e->getMessage(),
                    ];

                    Log::error('[ScrapImport] Chunk processing error', [
                        'actor_key' => $actorKey,
                        'chunk_index' => $chunkIndex + 1,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            $response = [
                'success' => true,
                'message' => "Imported {$importedCount} out of {$totalCount} jobs from [{$actorKey}]",
                'imported' => $importedCount,
                'total' => $totalCount,
                'chunks_processed' => count($jobChunks),
            ];

            if (! empty($failedChunks)) {
                $response['failed_chunks'] = $failedChunks;
                $response['warning'] = 'Some chunks failed to process due to timeout or errors';
            }

            return response()->json($response);
        } catch (\Throwable $e) {
            Log::error('[ScrapImport] importJobs failed', [
                'actor_key' => $actorKey,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function persistJobsIndeed(array $jobs)
    {
        $importedCount = 0;
        $dbChunkSize = 10; // Reduced from 100 to prevent timeout

        foreach (array_chunk($jobs, $dbChunkSize) as $jobChunk) {

            DB::beginTransaction();

            try {
                foreach ($jobChunk as $job) {

                    // ===============================
                    // COMPANY / OFFICE
                    // ===============================
                    $companyName = trim(
                        $job['companyName']
                            ?? $job['source']
                            ?? 'Unknown Company'
                    );
                    $companyUrl = $job['companyLinks']['corporateWebsite'] ?? null; // ✅ null safe
                    $emails = $job['emails'] ?? [];

                    $companyWebsite = null;
                    $companyEmail = null;
                    $companyPhone = null;

                    if (! $companyUrl) {
                        $companyDetails = $this->getScrappedCompanyWebsiteData($companyName);
                        $companyWebsite = $companyDetails['company_url'] ?? null;
                        $companyEmail = $companyDetails['company_email'] ?? null;
                        $companyPhone = $companyDetails['company_contact'] ?? null;
                    } else {
                        $companyWebsite = $companyUrl;
                    }

                    // ✅ Fixed — safely check both keys, fall back to empty string
                    $companyDesc = $job['companyDescription']
                        ?? $job['companyBriefDescription']
                        ?? $job['descriptionText'] // ← also try full description as last resort
                        ?? '';

                    $descriptionHtml = $job['descriptionHtml'] ?? null;
                    $descriptionText = $job['descriptionText'] ?? '';

                    $lat = $job['location']['latitude'] ?? null;
                    $lng = $job['location']['longitude'] ?? null;
                    $rawPostcode = $job['location']['postalCode'] ?? null;
                    $city = $job['location']['city'] ?? null;

                    $postcode = null;

                    // ✅ Use raw postcode if available
                    if ($rawPostcode) {
                        $postcode = trim($rawPostcode);
                    }

                    // ✅ Reverse geocode fallback
                    if (empty($postcode) && ! empty($lat) && ! empty($lng)) {
                        $postcode = $this->getScrappedPostcodes($lat, $lng);
                    }

                    $postcode = $postcode ?? 'UNKNOWN';

                    // ===============================
                    // LAT/LNG FALLBACK FROM POSTCODES TABLE
                    // ===============================
                    if ($postcode != 'UNKNOWN' && ! $lat || ! $lng) {
                        $postcode_query = DB::table('postcodes')
                            ->whereRaw("LOWER(REPLACE(postcode,' ','')) = ?", [strtolower(str_replace(' ', '', $postcode))])
                            ->first();

                        // 2. Fallback: If not found in full postcodes, check outcodes
                        if (! $postcode_query) {
                            $postcode_query = DB::table('outcodepostcodes')
                                ->whereRaw("LOWER(REPLACE(outcode, ' ', '')) = ?", [$postcode])
                                ->first();
                        }

                        // if (!$postcode_query) {
                        //     try {
                        //         $result = $this->geocode($postcode);

                        //         // If geocode fails, throw
                        //         if (!isset($result['lat']) || !isset($result['lng'])) {
                        //             throw new \Exception('Geolocation failed. Latitude and longitude not found.');
                        //         }

                        //         $lat = $result['lat'];
                        //         $lng = $result['lng'];
                        //     } catch (\Exception $e) {
                        //         Log::error('[ScrapImport] Geocode failed for postcode ' . $postcode . ': ' . $e->getMessage());
                        //     }
                        // } else {
                        $lat = $postcode_query->lat;
                        $lng = $postcode_query->lng;
                        // }

                    }

                    // ===============================
                    // OFFICE
                    // ===============================
                    $office = Office::whereRaw('LOWER(office_name) = ?', [strtolower($companyName)])->first();

                    if (! $office) {
                        $office = Office::create([
                            'office_name' => $companyName,
                            'office_postcode' => $postcode,
                            'user_id' => Auth::id(),
                            'office_type' => 'head_office',
                            'office_website' => $companyWebsite, // ✅ was using wrong var before
                            'office_notes' => $companyDesc ? $this->sanitizeForMysql(substr($companyDesc, 0, 500)) : '',
                            'office_lat' => $lat,
                            'office_lng' => $lng,
                            'status' => 4, // 4 = Scrapped
                        ]);

                        $office->update(['office_uid' => md5($office->id)]);
                    }

                    // ===============================
                    // COLLECT ALL CONTACTS
                    // ===============================
                    $contactsMap = [];

                    // ✅ Source 0: scraped company email/phone (was never saved before)
                    if (is_array($emails)) {
                        foreach ($emails as $email) {
                            $email = strtolower(trim($email));

                            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                                continue;
                            }

                            $exists = Contact::where('contact_email', $email)
                                ->where('contactable_type', Office::class)
                                ->exists();

                            if (!$exists && !isset($contactsMap[$email])) {
                                $contactsMap[$email] = [
                                    'contact_name' => $companyName,
                                    'contact_phone' => $companyPhone ?? null,
                                    'contact_email' => $email,
                                ];
                            }
                        }
                    }

                    // ✅ Source 0: scraped company email/phone (was never saved before)
                    if ($companyEmail && filter_var($companyEmail, FILTER_VALIDATE_EMAIL)) {
                        $email = strtolower(trim($companyEmail));
                        $exists = Contact::where('contact_email', $email)
                            ->where('contactable_type', Office::class)
                            ->exists();

                        if (! $exists) {
                            $contactsMap[$email] = [
                                'contact_name' => $companyName,
                                'contact_phone' => $companyPhone ?? null,
                                'contact_email' => $email,
                            ];
                        }
                    }

                    // Source 1: job['contacts'] array
                    if (! empty($job['contacts']) && is_array($job['contacts'])) {
                        foreach ($job['contacts'] as $c) {
                            $email = isset($c['contactEmail']) ? strtolower(trim($c['contactEmail'])) : null;
                            $name = isset($c['contactName']) ? trim($c['contactName']) : null;
                            $phone = isset($c['contactPhone']) ? trim($c['contactPhone']) : null;

                            if ($email && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                                $exists = Contact::where('contact_email', $email)
                                    ->where('contactable_type', Office::class)
                                    ->exists();

                                if ($exists || isset($contactsMap[$email])) {
                                    continue;
                                }

                                $contactsMap[$email] = [
                                    'contact_name' => $name ?? $companyName,
                                    'contact_phone' => $phone,
                                    'contact_email' => $email,
                                ];
                            }
                        }
                    }

                    // Source 2: extract from description
                    if (! empty($descriptionText)) {

                        // Pattern 1: Name (email@example.com)
                        if (
                            preg_match_all(
                                '/([A-Za-z][A-Za-z\.\'\-]+(?:\s[A-Za-z\.\'\-]+)+)\s*\(\s*([\w\.\-]+@[\w\.\-]+\.\w+)\s*\)/',
                                $descriptionText,
                                $matches,
                                PREG_SET_ORDER
                            )
                        ) {
                            foreach ($matches as $m) {
                                $email = strtolower(trim($m[2]));
                                $name = trim($m[1]);

                                if (filter_var($email, FILTER_VALIDATE_EMAIL) && ! isset($contactsMap[$email])) {
                                    $exists = Contact::where('contact_email', $email)
                                        ->where('contactable_type', Unit::class)
                                        ->exists();

                                    if (! $exists) {
                                        $contactsMap[$email] = [
                                            'contact_name' => $name,
                                            'contact_phone' => null,
                                            'contact_email' => $email,
                                        ];
                                    }
                                }
                            }
                        }

                        // Pattern 2: Bare email addresses
                        if (preg_match_all('/[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}/', $descriptionText, $bareMatches)) {
                            foreach ($bareMatches[0] as $email) {
                                $email = strtolower(trim($email));

                                if (filter_var($email, FILTER_VALIDATE_EMAIL) && ! isset($contactsMap[$email])) {
                                    $exists = Contact::where('contact_email', $email)
                                        ->where('contactable_type', Office::class)
                                        ->exists();

                                    if (! $exists) {
                                        $name = null;
                                        if (
                                            preg_match(
                                                '/([A-Z][a-z]+(?:\s[A-Z][a-z]+)+)\s*[:\-]?\s*' . preg_quote($email, '/') . '/',
                                                $descriptionText,
                                                $nameMatch
                                            )
                                        ) {
                                            $name = trim($nameMatch[1]);
                                        }

                                        $contactsMap[$email] = [
                                            'contact_name' => $name ?? $companyName,
                                            'contact_phone' => null,
                                            'contact_email' => $email,
                                        ];
                                    }
                                }
                            }
                        }

                        // Pattern 3: "Email: ..." or "Contact: ..."
                        if (
                            preg_match_all(
                                '/(?:email|contact|mailto|e-mail)\s*[:\-]?\s*([a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,})/i',
                                $descriptionText,
                                $labeledMatches
                            )
                        ) {
                            foreach ($labeledMatches[1] as $email) {
                                $email = strtolower(trim($email));

                                if (filter_var($email, FILTER_VALIDATE_EMAIL) && ! isset($contactsMap[$email])) {
                                    $exists = Contact::where('contact_email', $email)
                                        ->where('contactable_type', Office::class)
                                        ->exists();

                                    if (! $exists) {
                                        $contactsMap[$email] = [
                                            'contact_name' => $companyName,
                                            'contact_phone' => null,
                                            'contact_email' => $email,
                                        ];
                                    }
                                }
                            }
                        }
                    }

                    // ===============================
                    // SAVE ALL CONTACTS
                    // ===============================
                    foreach ($contactsMap as $email => $contact) {
                        Contact::updateOrCreate(
                            [
                                'contactable_id' => $office->id,
                                'contactable_type' => Office::class,
                                'contact_email' => $email,
                            ],
                            [
                                'contact_name' => $contact['contact_name'],
                                'contact_phone' => $contact['contact_phone'],
                            ]
                        );
                    }

                    // ===============================
                    // UNIT
                    // ===============================
                    $unitName = null;

                    if ($descriptionHtml) {
                        if (preg_match('/<b>Branch:\s*<\/b>\s*([^<]+)/i', $descriptionHtml, $matches)) {
                            $unitName = trim(html_entity_decode($matches[1], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
                        }
                    }

                    $unitName = $unitName ?: ($city ?? 'Main Unit');

                    $unit = Unit::where('office_id', $office->id)
                        ->whereRaw('LOWER(unit_name) = ?', [strtolower(trim($unitName))])
                        ->whereRaw("REPLACE(unit_postcode, ' ', '') = ?", [str_replace(' ', '', $postcode)])
                        ->first();

                    if (! $unit) {
                        $unitWebsite = null;
                        $unitEmail = null;
                        $unitPhone = null;

                        if ($unitName) {
                            $unitDetails = $this->getScrappedCompanyWebsiteData($unitName);
                            $unitWebsite = $unitDetails['company_url'] ?? null;
                            $unitEmail = $unitDetails['company_email'] ?? null;
                            $unitPhone = $unitDetails['company_contact'] ?? null;
                        }

                        $unit = Unit::create([
                            'office_id' => $office->id,
                            'unit_name' => $unitName,
                            'unit_postcode' => $postcode,
                            'user_id' => Auth::id(),
                            'unit_website' => $unitWebsite,
                            'unit_notes' => 'Scrapped from Indeed',
                            'lat' => $lat,
                            'lng' => $lng,
                            'status' => 4,
                            'unit_uid' => md5(uniqid()),
                        ]);

                        $contactsUnitMap = [];

                        // ✅ Source 0: scraped company email/phone (was never saved before)
                        if ($unitEmail && filter_var($unitEmail, FILTER_VALIDATE_EMAIL)) {
                            $email = strtolower(trim($unitEmail));
                            $exists = Contact::where('contact_email', $email)
                                ->where('contactable_type', Unit::class)
                                ->exists();

                            if (! $exists) {
                                $contactsUnitMap[$email] = [
                                    'contact_name' => $unitName,
                                    'contact_phone' => $unitPhone ?? null,
                                    'contact_email' => $email,
                                ];
                            }
                        }

                        foreach ($contactsUnitMap as $email => $contact) {
                            Contact::updateOrCreate(
                                [
                                    'contactable_id' => $unit->id,
                                    'contactable_type' => Unit::class,
                                    'contact_email' => $email,
                                ],
                                [
                                    'contact_name' => $contact['contact_name'],
                                    'contact_phone' => $contact['contact_phone'],
                                ]
                            );
                        }
                    }

                    // ===============================
                    // JOB TITLE
                    // ===============================
                    $rawTitle = trim(str_replace('-', ' ', $job['title'] ?? 'Generic Job'));

                    $jobTitle = JobTitle::where('name', $rawTitle)->first();

                    if ($jobTitle) {
                        $jobTitleId = $jobTitle->id;
                        $jobCategory = $jobTitle->job_category_id;
                        $jobConditionType = $jobTitle->type;
                    } else {
                        $jobCategory = 2;
                        $jobConditionType = 'regular';

                        $jobTitle = JobTitle::create([
                            'name' => $rawTitle,
                            'type' => $jobConditionType,
                            'job_category_id' => $jobCategory,
                            'description' => 'Scrapped from Indeed',
                            'is_active' => true,
                            'related_titles' => json_encode([]),
                        ]);

                        $jobTitleId = $jobTitle->id;
                    }

                    // ===============================
                    // DESCRIPTION PARSING
                    // ===============================
                    $qualification = 'Not specified';
                    if (preg_match('/\*Qualifications\*\s*(.+?)(\n\*|$)/s', $descriptionText, $m)) {
                        $qualification = trim($m[1]);
                    }

                    $experience = 'Not specified';
                    if (preg_match('/\*Experience\*\s*(.+?)(\n\*|$)/s', $descriptionText, $m)) {
                        $experience = trim($m[1]);
                    }

                    // ===============================
                    // TIMING / VACANCIES / BENEFITS
                    // ===============================
                    $jobTypes = $job['jobType'] ?? [];
                    $timing = count($jobTypes) ? implode(', ', array_map(fn($t) => str_replace('-', ' ', $t), $jobTypes)) : 'Not specified';
                    $vacancies = $job['numOfCandidates'] ?? 2;
                    $benefits = ! empty($job['benefits']) ? implode(', ', $job['benefits']) : 'None';

                    // ===============================
                    // JOB SOURCE  ✅ was crashing if null
                    // ===============================
                    $jobSource = JobSource::whereRaw('LOWER(name) = ?', ['indeed'])->first();

                    if (! $jobSource) {
                        Log::warning('[ScrapImport] JobSource "indeed" not found, skipping job.');
                        DB::rollBack();

                        continue;
                    }

                    // ===============================
                    // DUPLICATE CHECK
                    // ===============================
                    $jobUrl = $job['jobUrl'] ?? null;

                    $existingSale = Sale::where('office_id', $office->id)
                        ->where('unit_id', $unit->id)
                        ->where('job_title_id', $jobTitleId) // ✅ added title check for better dedup
                        ->whereRaw("REPLACE(sale_postcode,' ','') = ?", [str_replace(' ', '', $postcode)])
                        ->first();

                    if (! $existingSale) {
                        $sale = Sale::create([
                            'user_id' => Auth::id(),
                            'office_id' => $office->id,
                            'unit_id' => $unit->id,
                            'job_category_id' => $jobCategory,
                            'job_title_id' => $jobTitleId,
                            'job_source_id' => $jobSource->id,
                            'job_type' => $jobConditionType,
                            'position_type' => strtolower($timing),
                            'sale_postcode' => $postcode,
                            'cv_limit' => $vacancies,
                            'timing' => $timing,
                            'experience' => $experience,
                            'salary' => $job['salary']['salaryText'] ?? '',
                            'benefits' => $benefits,
                            'qualification' => $qualification,
                            'sale_notes' => 'Scrap Indeed Job - ' . $jobUrl,
                            'job_description' => $descriptionText,
                            'lat' => $lat,
                            'lng' => $lng,
                            'status' => 4,
                        ]);

                        $sale->update(['sale_uid' => md5($sale->id)]);

                        $importedCount++;
                    }
                }

                DB::commit();
            } catch (Exception $e) {
                DB::rollBack();
                Log::error('[ScrapImport] persistJobsIndeed row error: ' . $e->getMessage(), [
                    'job' => $job['jobUrl'] ?? 'unknown', // ✅ log which job failed
                ]);
            }
        }

        return $importedCount;
    }

    public function persistJobsTotalJob(array $jobs)
    {
        $importedCount = 0;
        $dbChunkSize = 10; // Reduced from 100 to prevent timeout

        foreach (array_chunk($jobs, $dbChunkSize) as $jobChunk) {

            DB::beginTransaction();

            try {

                foreach ($jobChunk as $job) {
                    // ===============================
                    // COMPANY / OFFICE
                    // ===============================

                    // Clean company name — strip trailing "\nView Profile" or similar
                    $companyName = trim(explode("\n", $job['companyName'] ?? 'Unknown Company')[0]);
                    $companyUrl = $job['companyURL'];
                    $companyDesc = $job['descriptionText'] ?? 'Scrapped from TotalJobs';
                    $descriptionText = $job['descriptionText'] ?? '';
                    $descriptionHtml = $job['descriptionHTML'] ?? '';

                    $companyWebsite = null;
                    $companyEmail = null;
                    $companyPhone = null;

                    if (! $companyUrl) {
                        $companyDetails = $this->getScrappedCompanyWebsiteData($companyName);
                        $companyWebsite = $companyDetails['company_url'];
                        $companyEmail = $companyDetails['company_email'];
                        $companyPhone = $companyDetails['company_contact'];
                    } else {
                        $companyWebsite = $companyUrl;
                    }

                    // ===============================
                    // PARSE LOCATION STRING
                    // e.g. "Dumfries (DG2), DG2 9JW"
                    // ===============================
                    $locationRaw = $job['location'] ?? '';
                    $postcode = null;
                    $city = null;
                    $lat = null;
                    $lng = null;

                    if (! empty($locationRaw)) {
                        // Extract UK postcode from end of string (e.g. "DG2 9JW")
                        if (preg_match('/([A-Z]{1,2}\d{1,2}[A-Z]?\s*\d[A-Z]{2})$/i', $locationRaw, $pcMatch)) {
                            $postcode = strtoupper(trim($pcMatch[1]));

                            // 1. Try to find a match in the full postcodes table first
                            $postcode_query = DB::table('postcodes')
                                ->whereRaw("LOWER(REPLACE(postcode, ' ', '')) = ?", [$postcode])
                                ->first();

                            // 2. Fallback: If not found in full postcodes, check outcodes
                            if (! $postcode_query) {
                                $postcode_query = DB::table('outcodepostcodes')
                                    ->whereRaw("LOWER(REPLACE(outcode, ' ', '')) = ?", [$postcode])
                                    ->first();
                            }

                            // if (!$postcode_query) {
                            //     try {
                            //         $result = $this->geocode($postcode);

                            //         // If geocode fails, throw
                            //         if (!isset($result['lat']) || !isset($result['lng'])) {
                            //             throw new \Exception('Geolocation failed. Latitude and longitude not found.');
                            //         }

                            //         $lat = $result['lat'];
                            //         $lng = $result['lng'];
                            //     } catch (\Exception $e) {
                            //         Log::error('[ScrapImport] Geocode failed for postcode ' . $postcode . ': ' . $e->getMessage());
                            //     }
                            // } else {
                            $lat = $postcode_query->lat;
                            $lng = $postcode_query->lng;
                            // }
                        }

                        // Extract city — everything before first "(" or ","
                        if (preg_match('/^([^,(]+)/', $locationRaw, $cityMatch)) {
                            $city = trim($cityMatch[1]);
                        }
                    }

                    $postcode = $postcode ?? 'UNKNOWN';

                    $office = Office::whereRaw('LOWER(office_name)=?', [strtolower(trim($companyName))])->first();

                    if (! $office) {
                        $office = Office::create([
                            'office_name' => $companyName,
                            'office_postcode' => $postcode,
                            'user_id' => Auth::id(),
                            'office_type' => 'head_office',
                            'office_website' => $companyWebsite,
                            'office_notes' => substr($companyDesc, 0, 500),
                            'office_lat' => $lat,
                            'office_lng' => $lng,
                            'status' => 4, // 4=Scrapped
                        ]);

                        $office->update(['office_uid' => md5($office->id)]);
                    }

                    // ===============================
                    // COLLECT ALL CONTACTS
                    // ===============================
                    $contactsMap = [];

                    // ✅ Source 0: scraped company email/phone (was never saved before)
                    if ($companyEmail && filter_var($companyEmail, FILTER_VALIDATE_EMAIL)) {
                        $email = strtolower(trim($companyEmail));
                        $exists = Contact::where('contact_email', $email)
                            ->where('contactable_type', Office::class)
                            ->exists();

                        if (! $exists) {
                            $contactsMap[$email] = [
                                'contact_name' => $companyName,
                                'contact_phone' => $companyPhone ?? null,
                                'contact_email' => $email,
                            ];
                        }
                    }

                    // Source 1: extract emails from description
                    if (! empty($descriptionText)) {

                        // Pattern 1: Name (email@example.com)
                        if (
                            preg_match_all(
                                '/([A-Za-z][A-Za-z\.\'\-]+(?:\s[A-Za-z\.\'\-]+)+)\s*\(\s*([\w\.\-]+@[\w\.\-]+\.\w+)\s*\)/',
                                $descriptionText,
                                $matches,
                                PREG_SET_ORDER
                            )
                        ) {
                            foreach ($matches as $m) {
                                $email = strtolower(trim($m[2]));
                                $name = trim($m[1]);

                                if (filter_var($email, FILTER_VALIDATE_EMAIL) && ! isset($contactsMap[$email])) {

                                    if (Contact::where('contact_email', $email)->where('contactable_type', Office::class)->exists()) {
                                        continue;
                                    }

                                    $contactsMap[$email] = ['contact_name' => $name, 'contact_phone' => null, 'contact_email' => $email];
                                }
                            }
                        }

                        // Pattern 2: Bare email addresses
                        if (preg_match_all('/[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}/', $descriptionText, $bareMatches)) {
                            foreach ($bareMatches[0] as $email) {
                                $email = strtolower(trim($email));

                                if (filter_var($email, FILTER_VALIDATE_EMAIL) && ! isset($contactsMap[$email])) {

                                    if (Contact::where('contact_email', $email)->where('contactable_type', Office::class)->exists()) {
                                        continue;
                                    }

                                    $name = null;
                                    if (preg_match('/([A-Z][a-z]+(?:\s[A-Z][a-z]+)+)\s*[:\-]?\s*' . preg_quote($email, '/') . '/', $descriptionText, $nameMatch)) {
                                        $name = trim($nameMatch[1]);
                                    }

                                    $contactsMap[$email] = ['contact_name' => $name ?? $companyName, 'contact_phone' => null, 'contact_email' => $email];
                                }
                            }
                        }

                        // Pattern 3: "Email: ..." or "Contact: ..."
                        if (preg_match_all('/(?:email|contact|mailto|e-mail)\s*[:\-]?\s*([a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,})/i', $descriptionText, $labeledMatches)) {
                            foreach ($labeledMatches[1] as $email) {
                                $email = strtolower(trim($email));

                                if (filter_var($email, FILTER_VALIDATE_EMAIL) && ! isset($contactsMap[$email])) {

                                    if (Contact::where('contact_email', $email)->where('contactable_type', Office::class)->exists()) {
                                        continue;
                                    }

                                    $contactsMap[$email] = ['contact_name' => $companyName, 'contact_phone' => null, 'contact_email' => $email];
                                }
                            }
                        }
                    }

                    // ===============================
                    // SAVE ALL CONTACTS
                    // ===============================
                    foreach ($contactsMap as $email => $contact) {
                        Contact::updateOrCreate(
                            ['contactable_id' => $office->id, 'contactable_type' => Office::class, 'contact_email' => $email],
                            ['contact_name' => $contact['contact_name'], 'contact_phone' => $contact['contact_phone']]
                        );
                    }

                    // ===============================
                    // UNIT
                    // ===============================
                    $unitName = null;

                    if ($descriptionHtml) {
                        if (preg_match('/<b>Branch:\s*<\/b>\s*([^<]+)/i', $descriptionHtml, $matches)) {
                            $unitName = trim(html_entity_decode($matches[1], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
                        }
                    }

                    $unitName = $unitName ?: ($city ?? 'Main Unit');

                    $unit = Unit::where('office_id', $office->id)
                        ->whereRaw('LOWER(unit_name)=?', [strtolower(trim($unitName))])
                        ->whereRaw("REPLACE(unit_postcode,' ','')=?", [str_replace(' ', '', $postcode)])
                        ->first();

                    if (! $unit) {
                        $unitWebsite = null;
                        $unitEmail = null;
                        $unitPhone = null;

                        if ($unitName) {
                            $unitDetails = $this->getScrappedCompanyWebsiteData($unitName);
                            $unitWebsite = $unitDetails['company_url'] ?? null;
                            $unitEmail = $unitDetails['company_email'] ?? null;
                            $unitPhone = $unitDetails['company_contact'] ?? null;
                        }

                        $unit = Unit::create([
                            'office_id' => $office->id,
                            'unit_name' => $unitName,
                            'unit_postcode' => $postcode,
                            'user_id' => Auth::id(),
                            'unit_website' => $unitWebsite,
                            'unit_notes' => 'Scrapped from TotalJobs',
                            'lat' => $lat,
                            'lng' => $lng,
                            'status' => 4, // 4 = scraped
                        ]);

                        $unit->update(['unit_uid' => md5($unit->id)]);

                        $contactsUnitMap = [];

                        // ✅ Source 0: scraped company email/phone (was never saved before)
                        if ($unitEmail && filter_var($unitEmail, FILTER_VALIDATE_EMAIL)) {
                            $email = strtolower(trim($unitEmail));
                            $exists = Contact::where('contact_email', $email)
                                ->where('contactable_type', Unit::class)
                                ->exists();

                            if (! $exists) {
                                $contactsUnitMap[$email] = [
                                    'contact_name' => $unitName,
                                    'contact_phone' => $unitPhone ?? null,
                                    'contact_email' => $email,
                                ];
                            }
                        }

                        foreach ($contactsUnitMap as $email => $contact) {
                            Contact::updateOrCreate(
                                [
                                    'contactable_id' => $unit->id,
                                    'contactable_type' => Unit::class,
                                    'contact_email' => $email,
                                ],
                                [
                                    'contact_name' => $contact['contact_name'],
                                    'contact_phone' => $contact['contact_phone'],
                                ]
                            );
                        }
                    }

                    // ===============================
                    // JOB TITLE
                    // ===============================
                    $rawTitle = str_replace('-', ' ', $job['title'] ?? 'Generic Job');
                    $jobTitle = JobTitle::where('name', $rawTitle)->first();

                    if ($jobTitle) {
                        $jobTitleId = $jobTitle->id;
                        $jobCategory = $jobTitle->job_category_id;
                        $jobConditionType = $jobTitle->type;
                    } else {
                        $jobCategory = 2;
                        $jobConditionType = 'regular';
                        $jobTitle = JobTitle::create([
                            'name' => $rawTitle,
                            'type' => $jobConditionType,
                            'job_category_id' => $jobCategory,
                            'description' => 'Scrapped from TotalJobs',
                            'is_active' => true,
                            'related_titles' => json_encode([]),
                        ]);
                        $jobTitleId = $jobTitle->id;
                    }

                    // ===============================
                    // DESCRIPTION PARSING
                    // ===============================
                    $qualification = 'Not specified';
                    if (preg_match('/\*Qualifications\*\s*(.+?)(\n\*|$)/s', $descriptionText, $m)) {
                        $qualification = trim($m[1]);
                    }

                    $experience = 'Not specified';
                    if (preg_match('/\*Experience\*\s*(.+?)(\n\*|$)/s', $descriptionText, $m)) {
                        $experience = trim($m[1]);
                    }

                    // ===============================
                    // TIMING / VACANCIES / BENEFITS
                    // jobType is a plain string in TotalJobs, not an array
                    // ===============================
                    $jobTypeRaw = $job['jobType'] ?? '';
                    $timing = ! empty($jobTypeRaw)
                        ? str_replace('-', ' ', $jobTypeRaw)   // e.g. "Permanent"
                        : 'Not specified';

                    $vacancies = $job['numOfCandidates'] ?? 2;
                    $benefits = isset($job['benefits']) && is_array($job['benefits'])
                        ? implode(', ', $job['benefits'])
                        : 'None';

                    // ===============================
                    // DUPLICATE CHECK
                    // TotalJobs has no jobUrl — use numeric id instead
                    // ===============================
                    $jobUrl = $job['companyURL'] ?? null;
                    $jobRef = 'Scrap TotalJobs Job - ' . $jobUrl;

                    $existingSale = Sale::where('office_id', $office->id)
                        ->where('unit_id', $unit->id)
                        ->whereRaw("REPLACE(sale_postcode,' ','')=?", [str_replace(' ', '', $postcode)])
                        ->first();

                    $jobSource = JobSource::whereRaw('LOWER(name) = ?', ['total job'])->first();

                    if (! $existingSale) {
                        $sale = Sale::create([
                            'user_id' => Auth::id(),
                            'office_id' => $office->id,
                            'unit_id' => $unit->id,
                            'job_category_id' => $jobCategory,
                            'job_title_id' => $jobTitleId,
                            'job_source_id' => $jobSource->id,
                            'job_type' => $jobConditionType,
                            'position_type' => strtolower($timing),
                            'sale_postcode' => $postcode,
                            'cv_limit' => $vacancies,
                            'timing' => $timing,
                            'experience' => $experience,
                            'salary' => $job['salaryRangeRaw'] ?? '',  // TotalJobs field
                            'benefits' => $benefits,
                            'qualification' => $qualification,
                            'sale_notes' => $jobRef,
                            'job_description' => $descriptionText,
                            'lat' => $lat,
                            'lng' => $lng,
                            'status' => 4,
                        ]);

                        $sale->update(['sale_uid' => md5($sale->id)]);
                    }
                    $importedCount++;
                }

                DB::commit();
            } catch (Exception $e) {
                DB::rollBack();
                Log::error('[ScrapImport] persistJobsTotalJob row error: ' . $e->getMessage(), [
                    'job_id' => $job['id'] ?? null,
                ]);
            }
        }

        return $importedCount;
    }

    public function persistJobsReed(array $jobs)
    {
        $importedCount = 0;
        $dbChunkSize = 10; // Reduced from 100 to prevent timeout

        foreach (array_chunk($jobs, $dbChunkSize) as $jobChunk) {

            DB::beginTransaction();

            try {

                foreach ($jobChunk as $job) {
                    // ===============================
                    // COMPANY / OFFICE
                    // ===============================
                    $companyName = trim($job['company'] ?? $job['ouName'] ?? 'Unknown Company');
                    // $companyUrl = $job['job_profileUrl'] ?? $job['employerUrl'] ?? null;
                    $companyDesc = $job['description_text'] ?? 'Scrapped from Reed';
                    $descriptionText = $job['description_text'] ?? '';
                    $descriptionHtml = $job['description_html'] ?? '';

                    $companyDetails = $this->getScrappedCompanyWebsiteData($companyName);
                    $companyUrl = $companyDetails['company_url'] ?? null;
                    $companyEmail = $companyDetails['company_email'] ?? null;
                    $companyPhone = $companyDetails['company_contact'] ?? null;

                    // ===============================
                    // LOCATION
                    // ===============================
                    $locationRaw = $job['location'] ?? null;
                    $postcode = null;
                    $lat = null;
                    $lng = null;

                    if (! empty($locationRaw)) {
                        // Extract UK postcode from end of string (e.g. "DG2 9JW")
                        if (preg_match('/([A-Z]{1,2}\d{1,2}[A-Z]?\s*\d[A-Z]{2})$/i', $locationRaw, $pcMatch)) {
                            $postcode = strtoupper(trim($pcMatch[1]));

                            // 1. Try to find a match in the full postcodes table first
                            $postcode_query = DB::table('postcodes')
                                ->whereRaw("LOWER(REPLACE(postcode, ' ', '')) = ?", [$postcode])
                                ->first();

                            // 2. Fallback: If not found in full postcodes, check outcodes
                            if (! $postcode_query) {
                                $postcode_query = DB::table('outcodepostcodes')
                                    ->whereRaw("LOWER(REPLACE(outcode, ' ', '')) = ?", [$postcode])
                                    ->first();
                            }

                            // if (!$postcode_query) {
                            //     try {
                            //         $result = $this->geocode($postcode);

                            //         // If geocode fails, throw
                            //         if (!isset($result['lat']) || !isset($result['lng'])) {
                            //             throw new \Exception('Geolocation failed. Latitude and longitude not found.');
                            //         }

                            //         $lat = $result['lat'];
                            //         $lng = $result['lng'];
                            //     } catch (\Exception $e) {
                            //         Log::error('[ScrapImport] Geocode failed for postcode ' . $postcode . ': ' . $e->getMessage());
                            //     }
                            // } else {
                            $lat = $postcode_query->lat;
                            $lng = $postcode_query->lng;
                            // }
                        }

                        // Extract city — everything before first "(" or ","
                        if (preg_match('/^([^,(]+)/', $locationRaw, $cityMatch)) {
                            $city = trim($cityMatch[1]);
                        }
                    }

                    $postcode = $postcode ?? 'UNKNOWN';

                    // ===============================
                    // OFFICE
                    // ===============================
                    $office = Office::whereRaw('LOWER(office_name)=?', [strtolower($companyName)])->first();

                    if (! $office) {
                        $office = Office::create([
                            'office_name' => $companyName,
                            'office_postcode' => $postcode,
                            'user_id' => Auth::id(),
                            'office_type' => 'head_office',
                            'office_website' => $companyUrl,
                            'office_notes' => substr($companyDesc, 0, 500),
                            'office_lat' => $lat,
                            'office_lng' => $lng,
                            'status' => 4, // scrapped
                        ]);

                        $office->update(['office_uid' => md5($office->id)]);
                    }

                    // ===============================
                    // CONTACTS (same logic, just change source)
                    // ===============================
                    $contactsMap = [];

                    // ✅ Source 0: scraped company email/phone (was never saved before)
                    if ($companyEmail && filter_var($companyEmail, FILTER_VALIDATE_EMAIL)) {
                        $email = strtolower(trim($companyEmail));
                        $exists = Contact::where('contact_email', $email)
                            ->where('contactable_type', Office::class)
                            ->exists();

                        if (! $exists) {
                            $contactsMap[$email] = [
                                'contact_name' => $companyName,
                                'contact_phone' => $companyPhone ?? null,
                                'contact_email' => $email,
                            ];
                        }
                    }

                    if (! empty($descriptionText)) {

                        preg_match_all('/[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}/', $descriptionText, $matches);

                        foreach ($matches[0] as $email) {
                            $email = strtolower(trim($email));

                            if (! isset($contactsMap[$email])) {
                                $contactsMap[$email] = [
                                    'contact_name' => $companyName,
                                    'contact_phone' => null,
                                    'contact_email' => $email,
                                ];
                            }
                        }
                    }

                    foreach ($contactsMap as $email => $contact) {
                        Contact::updateOrCreate(
                            [
                                'contactable_id' => $office->id,
                                'contactable_type' => Office::class,
                                'contact_email' => $email,
                            ],
                            [
                                'contact_name' => $contact['contact_name'],
                                'contact_phone' => $contact['contact_phone'],
                            ]
                        );
                    }

                    // ===============================
                    // UNIT
                    // ===============================
                    $unitName = null;

                    if ($descriptionHtml) {
                        if (preg_match('/<b>Branch:\s*<\/b>\s*([^<]+)/i', $descriptionHtml, $matches)) {
                            $unitName = trim(html_entity_decode($matches[1], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
                        }
                    }

                    $unitName = $unitName ?: ($city ?? 'Main Unit');

                    $unit = Unit::where('office_id', $office->id)
                        ->whereRaw('LOWER(unit_name) = ?', [strtolower(trim($unitName))])
                        ->whereRaw("REPLACE(unit_postcode, ' ', '') = ?", [str_replace(' ', '', $postcode)])
                        ->first();

                    if (! $unit) {
                        $unitWebsite = null;
                        $unitEmail = null;
                        $unitPhone = null;

                        if ($unitName) {
                            $unitDetails = $this->getScrappedCompanyWebsiteData($unitName);
                            $unitWebsite = $unitDetails['company_url'] ?? null;
                            $unitEmail = $unitDetails['company_email'] ?? null;
                            $unitPhone = $unitDetails['company_contact'] ?? null;
                        }

                        $unit = Unit::create([
                            'office_id' => $office->id,
                            'unit_name' => $unitName,
                            'unit_postcode' => $postcode,
                            'user_id' => Auth::id(),
                            'unit_website' => $unitWebsite,
                            'unit_notes' => 'Scrapped from Reed',
                            'lat' => $lat,
                            'lng' => $lng,
                            'status' => 4, // scrapped
                        ]);

                        $unit->update(['unit_uid' => md5($unit->id)]);

                        $contactsUnitMap = [];

                        // ✅ Source 0: scraped company email/phone (was never saved before)
                        if ($unitEmail && filter_var($unitEmail, FILTER_VALIDATE_EMAIL)) {
                            $email = strtolower(trim($unitEmail));
                            $exists = Contact::where('contact_email', $email)
                                ->where('contactable_type', Unit::class)
                                ->exists();

                            if (! $exists) {
                                $contactsUnitMap[$email] = [
                                    'contact_name' => $unitName,
                                    'contact_phone' => $unitPhone ?? null,
                                    'contact_email' => $email,
                                ];
                            }
                        }

                        foreach ($contactsUnitMap as $email => $contact) {
                            Contact::updateOrCreate(
                                [
                                    'contactable_id' => $unit->id,
                                    'contactable_type' => Unit::class,
                                    'contact_email' => $email,
                                ],
                                [
                                    'contact_name' => $contact['contact_name'],
                                    'contact_phone' => $contact['contact_phone'],
                                ]
                            );
                        }
                    }

                    // ===============================
                    // JOB TITLE
                    // ===============================
                    $rawTitle = $job['jobTitle'] ?? '';

                    $jobTitle = JobTitle::where('name', $rawTitle)->first();

                    if (! $jobTitle) {
                        $jobTitle = JobTitle::create([
                            'name' => $rawTitle,
                            'type' => 'regular',
                            'job_category_id' => 2,
                            'description' => 'Scrapped from Reed',
                            'is_active' => true,
                            'related_titles' => json_encode([]),
                        ]);
                    }

                    // ===============================
                    // JOB DETAILS
                    // ===============================
                    $timing = $job['employmentType'] ?? 'Not specified';

                    $salary = $job['salary'] ??
                        ($job['salaryMin'] && $job['salaryMax']
                            ? $job['salaryMin'] . ' - ' . $job['salaryMax']
                            : '');

                    // Normalize spacing
                    $salary = trim($salary) . ' per annum';

                    // Add £ if no symbol found
                    if ($salary && ! preg_match('/[£$€]/u', $salary)) {
                        $salary = '£' . $salary;
                    }

                    // ===============================
                    // DESCRIPTION PARSING
                    // ===============================
                    $qualification = [];

                    if (! empty($descriptionText)) {

                        // Capture Level qualifications
                        preg_match_all('/Level\s*\d+\s+[A-Za-z\s]+qualification[^.,]*/i', $descriptionText, $levelMatches);

                        // Capture NMC registration
                        preg_match_all('/NMC\s+registration[^.,]*/i', $descriptionText, $nmcMatches);

                        $all = array_merge($levelMatches[0], $nmcMatches[0]);

                        if (! empty($all)) {
                            $qualification = implode(', ', array_unique(array_map('trim', $all)));
                        } else {
                            $qualification = 'Not specified';
                        }
                    }

                    $experience = 'Not specified';

                    if (! empty($descriptionText)) {
                        if (preg_match('/(minimum\s+\d+.*?experience.*?)(?:\.|\n)/i', $descriptionText, $m)) {
                            $experience = trim($m[1]);
                        }
                    }

                    // ===============================
                    // DUPLICATE CHECK
                    // ===============================
                    $jobUrl = $job['job_url'] ?? $job['url'] ?? null;

                    $existingSale = Sale::where('sale_notes', 'LIKE', '%' . $jobUrl . '%')->first();

                    $jobSource = JobSource::whereRaw('LOWER(name) = ?', ['reed'])->first();

                    if (! $existingSale) {
                        $sale = Sale::create([
                            'user_id' => Auth::id(),
                            'office_id' => $office->id,
                            'unit_id' => $unit->id,
                            'job_category_id' => $jobTitle->job_category_id,
                            'job_title_id' => $jobTitle->id,
                            'job_source_id' => $jobSource->id,
                            'job_type' => $jobTitle->type,
                            'position_type' => strtolower($timing),
                            'sale_postcode' => $postcode,
                            'cv_limit' => 2,
                            'timing' => $timing,
                            'experience' => $experience,
                            'salary' => $salary,
                            'benefits' => 'N/A',
                            'qualification' => $qualification,
                            'sale_notes' => 'Reed Job - ' . $jobUrl,
                            'job_description' => $job['jobDescription'] ?? $descriptionText,
                            'lat' => $lat,
                            'lng' => $lng,
                            'status' => 4,
                        ]);

                        $sale->update(['sale_uid' => md5($sale->id)]);
                    }
                    $importedCount++;
                }

                DB::commit();
            } catch (Exception $e) {
                DB::rollBack();
                Log::error('[ScrapImport] persistJobsReed error: ' . $e->getMessage(), [
                    'job_id' => $job['jobId'] ?? null,
                ]);
            }
        }

        return $importedCount;
    }

    public function officeIndex()
    {
        return view('scrapped.offices_list');
    }

    private function getScrappedCompanyWebsiteData(string $companyName)
    {
        $blank = [
            'company_url' => null,
            'company_email' => null,
            'company_contact' => null,
        ];

        try {
            $response = Http::withOptions([
                'connect_timeout' => 3,
                'timeout' => 10,
            ])->get('https://serpapi.com/search', [  // ✅ correct URL
                'q' => $companyName . ' uk official website',
                'api_key' => 'a7dde0e1efc3804c6388c6dad235a60e5d9a4a2f30f4c8141d0f2b28dd67b8ff', // SERPAPI_KEY
                'num' => 1,   // ✅ only fetch 1 result to save credits
                'engine' => 'google', // ✅ explicitly set engine
            ]);

            if (! $response->ok()) {
                Log::warning('[ScrapImport] Company lookup failed', [
                    'company_name' => $companyName,
                    'status' => $response->status(),
                    'body' => substr($response->body(), 0, 500),
                ]);

                return $blank;
            }

            $results = $response->json();
            $companyUrl = $results['organic_results'][0]['link'] ?? null;

            if (empty($companyUrl)) {
                return $blank;
            }

            $email = null;
            $phone = null;
            $contactUrl = null;
            $sitelinks = $results['organic_results'][0]['sitelinks']['expanded'] ?? [];

            foreach ($sitelinks as $sitelink) {
                $link = $sitelink['link'] ?? '';
                $title = strtolower($sitelink['title'] ?? '');

                if (str_contains(strtolower($link), 'contact') || str_contains($title, 'contact') || str_contains($link, 'about') || str_contains($title, 'about')) {
                    $contactUrl = $link;
                    break;
                }
            }

            $homeHtml = $this->fetchHtml($companyUrl);
            if ($homeHtml) {
                $homeDetails = $this->extractContactDetailsFromHtml($homeHtml);
                $email = $homeDetails['email'] ?? $email;
                $phone = $homeDetails['phone'] ?? $phone;

                if (empty($contactUrl)) {
                    $contactUrl = $this->guessContactPageFromHtml($companyUrl, $homeHtml);
                }
            }

            $candidateUrls = array_filter(array_unique(array_merge([
                $contactUrl,
                $companyUrl,
            ], $this->discoverContactPageUrls($companyUrl, $homeHtml ?? ''))));

            foreach ($candidateUrls as $url) {
                if (empty($url) || $url === $companyUrl) {
                    continue;
                }

                $details = $this->fetchContactDetails($url);
                $email = $email ?? $details['email'];
                $phone = $phone ?? $details['phone'];

                if ($email && $phone) {
                    break;
                }
            }

            return [
                'company_url' => $companyUrl,
                'company_email' => $email,
                'company_contact' => $phone,
            ];
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::warning('[ScrapImport] Company lookup timed out', [
                'company_name' => $companyName,
                'error' => $e->getMessage(),
            ]);

            return $blank;
        } catch (\Throwable $e) {
            Log::warning('[ScrapImport] Company lookup failed unexpectedly', [
                'company_name' => $companyName,
                'error' => $e->getMessage(),
            ]);

            return $blank;
        }
    }

    private function fetchHtml(string $url): ?string
    {
        try {
            $response = Http::withOptions([
                'connect_timeout' => 3,
                'timeout' => 10,
            ])->withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                'Accept' => 'text/html,application/xhtml+xml',
            ])->get($url);

            if (! $response->ok()) {
                return null;
            }

            return $response->body();
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function extractContactDetailsFromHtml(string $html): array
    {
        $plainText = $this->htmlToPlainText($html);

        return [
            'email' => $this->extractEmail($html, $plainText),
            'phone' => $this->extractPhone($plainText),
        ];
    }

    private function htmlToPlainText(string $html): string
    {
        $html = preg_replace('/<(script|style)[^>]*>.*?<\/\1>/is', ' ', $html);
        $html = preg_replace('/<br\s*\/?>/i', "\n", $html);
        $html = preg_replace('/<\/p>|<\/div>|<\/li>|<\/tr>|<\/h[1-6]>/i', "\n", $html);

        $text = strip_tags($html);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/[\r\t]+/', "\n", $text);
        $text = preg_replace('/\n+/', "\n", $text);
        $text = trim($text);

        return $text;
    }

    private function discoverContactPageUrls(string $baseUrl, string $html): array
    {
        if (empty($html)) {
            return [];
        }

        $keywords = [
            'contact',
            'about',
            'team',
            'support',
            'help',
            'newsletter',
            'subscribe',
            'location',
            'enquiry',
            'careers',
        ];

        preg_match_all('/<a[^>]+href=["\']([^"\']+)["\'][^>]*>(.*?)<\/a>/is', $html, $matches, PREG_SET_ORDER);

        $urls = [];
        foreach ($matches as $match) {
            $href = trim($match[1]);
            $text = strtolower(strip_tags($match[2]));

            if (empty($href) || str_starts_with($href, 'mailto:') || str_starts_with($href, 'tel:') || str_starts_with($href, 'javascript:') || str_starts_with($href, '#')) {
                continue;
            }

            $normalized = $this->normalizeUrl($href, $baseUrl);
            if (empty($normalized)) {
                continue;
            }

            foreach ($keywords as $keyword) {
                if (str_contains(strtolower($href), $keyword) || str_contains($text, $keyword)) {
                    $urls[] = $normalized;
                    break;
                }
            }
        }

        return array_values(array_unique($urls));
    }

    private function normalizeUrl(string $href, string $baseUrl): ?string
    {
        $href = trim($href);

        if (str_starts_with($href, '//')) {
            $scheme = parse_url($baseUrl, PHP_URL_SCHEME) ?: 'https';
            return $scheme . ':' . $href;
        }

        if (preg_match('#^https?://#i', $href)) {
            return $href;
        }

        $baseParts = parse_url($baseUrl);
        if (empty($baseParts['scheme']) || empty($baseParts['host'])) {
            return null;
        }

        $scheme = $baseParts['scheme'];
        $host = $baseParts['host'];
        $baseRoot = $scheme . '://' . $host;

        if (str_starts_with($href, '/')) {
            return $baseRoot . $href;
        }

        $path = $baseParts['path'] ?? '/';
        $dirname = rtrim(str_replace('\\', '/', dirname($path)), '/');
        if ($dirname === '') {
            $dirname = '/';
        }

        return $baseRoot . $dirname . '/' . ltrim($href, '/');
    }

    private function guessContactPageFromHtml(string $baseUrl, string $html): ?string
    {
        $urls = $this->discoverContactPageUrls($baseUrl, $html);
        return $urls[0] ?? null;
    }

    private function getScrappedPostcodes(float $lat, float $lng)
    {
        try {
            $response = Http::withOptions([
                'connect_timeout' => 3,  // ✅ max 3s to establish connection
                'timeout' => 10,  // ✅ max 5s for full response
            ])->get('https://api.postcodes.io/postcodes', [
                'lon' => $lng,
                'lat' => $lat,
                'limit' => 1,
            ]);

            if (! $response->successful()) {
                Log::warning('[ScrapImport] Postcodes API failed', [
                    'status' => $response->status(),
                    'lat' => $lat,
                    'lng' => $lng,
                ]);

                return null;
            }

            $data = $response->json();

            return $data['result'][0]['postcode'] ?? null;
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            // ✅ Catches timeout specifically
            Log::warning('[ScrapImport] Postcodes API timed out', [
                'lat' => $lat,
                'lng' => $lng,
            ]);

            return null;
        } catch (\Throwable $e) {
            Log::warning('[ScrapImport] Reverse geocode failed', [
                'error' => $e->getMessage(),
                'lat' => $lat,
                'lng' => $lng,
            ]);

            return null;
        }
    }

    private function sanitizeForMysql(string $value)
    {
        if (empty($value)) {
            return null;
        }

        // 1. Ensure valid UTF-8 (fix broken encoding)
        $value = mb_convert_encoding($value, 'UTF-8', 'UTF-8');
        $value = iconv('UTF-8', 'UTF-8//IGNORE', $value);

        // 2. Remove invisible/control characters (except line breaks & tabs)
        $value = preg_replace('/[^\P{C}\n\t]/u', '', $value);

        // 3. Normalize problematic unicode (smart quotes, dashes, etc.)
        $replace = [
            '’' => "'",
            '‘' => "'",
            '“' => '"',
            '”' => '"',
            '–' => '-',
            '—' => '-',
            '…' => '...',
        ];
        $value = strtr($value, $replace);

        // 4. Strip HTML (scraped content often contains junk)
        $value = strip_tags($value);

        // 5. Trim and normalize whitespace
        $value = preg_replace('/\s+/', ' ', $value);
        $value = trim($value);

        // 6. Final safety: limit length (don’t trust earlier substr)
        return mb_substr($value, 0, 500);
    }

    // ✅ Scrape contact page HTML using SerpApi
    private function fetchContactDetails(?string $contactPageUrl): array
    {
        $blank = ['email' => null, 'phone' => null];

        if (! $contactPageUrl) {
            return $blank;
        }

        try {
            $response = Http::withOptions([
                'connect_timeout' => 3,
                'timeout' => 8,
            ])->withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                'Accept' => 'text/html,application/xhtml+xml',
            ])->get($contactPageUrl);

            if (! $response->ok()) {
                Log::warning('[ScrapImport] Contact page fetch failed', [
                    'url' => $contactPageUrl,
                    'status' => $response->status(),
                ]);

                return $blank;
            }

            // ✅ Strip HTML tags and decode entities before parsing
            $html = $response->body();
            $plainText = $this->htmlToPlainText($html);

            return [
                'email' => $this->extractEmail($html, $plainText),
                'phone' => $this->extractPhone($plainText),
            ];
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::warning('[ScrapImport] Contact page timeout', ['url' => $contactPageUrl]);

            return $blank;
        } catch (\Throwable $e) {
            Log::warning('[ScrapImport] Contact page error', [
                'url' => $contactPageUrl,
                'error' => $e->getMessage(),
            ]);

            return $blank;
        }
    }

    // -------------------------------------------------------

    private function extractEmail(string $html, string $plainText): ?string
    {
        $email = null;

        // ✅ Priority 1: mailto: links — most reliable source on contact pages
        if (preg_match('/href=["\']mailto:([a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,})["\']/', $html, $m)) {
            $email = strtolower(trim($m[1]));

            if ($this->isValidContactEmail($email)) {
                return $email;
            }
        }

        // ✅ Priority 2: Labeled email in plain text e.g. "Email: info@company.com"
        if (
            preg_match(
                '/(?:email|e-mail|contact us|enquiries)\s*[:\-]?\s*([a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,})/i',
                $plainText,
                $m
            )
        ) {
            $email = strtolower(trim($m[1]));

            if ($this->isValidContactEmail($email)) {
                return $email;
            }
        }

        // ✅ Priority 3: Any email in plain text (fallback)
        if (
            preg_match_all(
                '/[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}/',
                $plainText,
                $allMatches
            )
        ) {
            foreach ($allMatches[0] as $candidate) {
                $candidate = strtolower(trim($candidate));

                if ($this->isValidContactEmail($candidate)) {
                    return $candidate;
                }
            }
        }

        return null;
    }

    // -------------------------------------------------------

    private function extractPhone(string $plainText): ?string
    {
        $plainText = trim($plainText);
        if ($plainText === '') {
            return null;
        }

        $found = [];

        // Match labeled phone entries like "Office: 0330 055 2205" or "Jenna - 01923 603 765"
        preg_match_all(
            '/(?:^|\n)\s*([A-Za-z][A-Za-z0-9\s&()\/\-]{1,50}?)\s*[:\-]\s*(\+44\s?[0-9\s\-.()]{7,}|0[0-9\s\-.()]{9,})(?=\s|$|\n)/i',
            $plainText,
            $labelMatches,
            PREG_SET_ORDER
        );

        foreach ($labelMatches as $match) {
            $label = trim($match[1]);
            $phone = $this->normalizePhone($match[2]);

            if ($phone === null) {
                continue;
            }

            $found[] = $label . ': ' . $phone;
        }

        if (! empty($found)) {
            return implode(' | ', array_unique($found));
        }

        $phones = [];
        preg_match_all('/(\+44\s?[0-9\s\-.()]{7,}|0[0-9\s\-.()]{9,})/', $plainText, $matches);
        foreach ($matches[1] as $match) {
            $phone = $this->normalizePhone($match);
            if ($phone === null) {
                continue;
            }
            $phones[] = $phone;
        }

        $phones = array_values(array_unique($phones));
        if (! empty($phones)) {
            return implode(' | ', $phones);
        }

        return null;
    }

    private function normalizePhone(string $phone): ?string
    {
        $digitsOnly = preg_replace('/\D+/', '', $phone);
        if (strlen($digitsOnly) < 10 || strlen($digitsOnly) > 14) {
            return null;
        }

        $clean = preg_replace('/[^\d+ ]+/', '', trim($phone));
        $clean = preg_replace('/\s+/', ' ', $clean);

        return $clean;
    }

    // -------------------------------------------------------

    private function isValidContactEmail(string $email): bool
    {
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        // ✅ Reject obvious non-contact emails
        $blacklistedExtensions = ['png', 'jpg', 'gif', 'jpeg', 'webp', 'svg', 'css', 'js', 'woff'];
        $extension = strtolower(pathinfo(explode('@', $email)[0], PATHINFO_EXTENSION));
        if (in_array($extension, $blacklistedExtensions)) {
            return false;
        }

        // ✅ Reject common system/noreply addresses
        $blacklistedPrefixes = ['noreply', 'no-reply', 'donotreply', 'mailer', 'bounce', 'postmaster', 'webmaster'];
        $prefix = strtolower(explode('@', $email)[0]);
        if (in_array($prefix, $blacklistedPrefixes)) {
            return false;
        }

        // ✅ Reject common example/placeholder domains
        $blacklistedDomains = ['example.com', 'test.com', 'sentry.io', 'wixpress.com'];
        $domain = strtolower(explode('@', $email)[1]);
        if (in_array($domain, $blacklistedDomains)) {
            return false;
        }

        return true;
    }

    public function unitIndex()
    {
        $offices = Office::where('status', 4)->orderBy('office_name', 'asc')->get();

        return view('scrapped.units_list', compact('offices'));
    }

    public function salesIndex()
    {
        $jobCategories = JobCategory::where('is_active', 1)->orderBy('name', 'asc')->get();
        $jobTitles = JobTitle::where('is_active', 1)->orderBy('name', 'asc')->get();
        $sources = JobSource::where('is_active', 1)->orderBy('name', 'asc')->get();
        $offices = Office::where('status', 4)->orderBy('office_name', 'asc')->get();
        $users = User::where('is_active', 1)->orderBy('name', 'asc')->get();

        return view('scrapped.sales_list', compact('jobCategories', 'jobTitles', 'offices', 'users', 'sources'));
    }

    public function getScrappedOffices(Request $request)
    {
        $statusFilter = $request->input('status_filter', '');

        // Base query
        $model = Office::withTrashed()
            ->with(['contact']) // Eager load contact relationship to solve N+1 Problem
            ->leftJoinSub(
                DB::table('contacts')
                    ->select([
                        'contactable_id',
                        DB::raw('GROUP_CONCAT(DISTINCT contact_email SEPARATOR ", ") as office_emails'),
                        DB::raw('GROUP_CONCAT(DISTINCT contact_phone SEPARATOR ", ") as office_phones'),
                        DB::raw('GROUP_CONCAT(DISTINCT contact_landline SEPARATOR ", ") as office_landlines'),
                    ])
                    ->where('contactable_type', 'Horsefly\\Office')
                    ->groupBy('contactable_id'),
                'office_contacts',
                'office_contacts.contactable_id',
                '=',
                'offices.id'
            )
            ->where('offices.status', 4)
            ->select('offices.*')
            ->distinct();

        if ($statusFilter === 'deleted') {
            $model->where('offices.status', 4)
                ->onlyTrashed(); // 👈 BEST & CLEAN
        } else {
            $model->where('offices.status', 4)
                ->whereNull('offices.deleted_at');
        }

        // Handle search input
        if ($request->filled('search.value')) {
            $search = trim($request->input('search.value'));

            if (strlen($search) >= 2) {
                // Get office IDs matching the search query via Laravel Scout
                $officeIds = Office::search($search)->keys()->toArray();

                // Find contact IDs matching the search for contact fields
                $contactIds = Contact::where('contactable_type', 'Horsefly\\Office')
                    ->where(function ($q) use ($search) {
                        $q->where('contact_email', 'LIKE', "%{$search}%")
                            ->orWhere('contact_phone', 'LIKE', "%{$search}%")
                            ->orWhere('contact_landline', 'LIKE', "%{$search}%");
                    })->pluck('contactable_id')->toArray();

                // Merge and get unique IDs from both searches
                $allMatchingIds = array_unique(array_merge($officeIds, $contactIds));

                // Filter offices based on the combined matching IDs
                if (! empty($allMatchingIds)) {
                    $model->whereIn('offices.id', $allMatchingIds);
                }
            }
        }

        // Sorting logic
        if ($request->has('order')) {
            $orderColumnIndex = $request->input('order.0.column');
            $orderColumn = $request->input("columns.$orderColumnIndex.data");
            $orderDirection = $request->input('order.0.dir', 'asc');

            // List of columns that are not actual database columns and should be skipped
            $nonSortableColumns = [
                'checkbox',
                'action',
                // add other non-database columns here if needed
            ];

            if ($orderColumn && $orderColumn !== 'DT_RowIndex' && ! in_array($orderColumn, $nonSortableColumns)) {
                // Map the column if needed, or directly use the column name
                // Example: if you want to map 'office_name' to 'offices.name', do it here
                $columnMap = [
                    'office_name' => 'offices.office_name',
                    'office_postcode' => 'offices.office_postcode',
                    'office_type' => 'offices.office_type',
                    'contact_email' => 'office_contacts.office_emails',
                    'contact_phone' => 'office_contacts.office_phones',
                    'contact_landline' => 'office_contacts.office_landlines',
                    'created_at' => 'offices.created_at',
                    'updated_at' => 'offices.updated_at',
                ];

                if (isset($columnMap[$orderColumn])) {
                    $model->orderBy($columnMap[$orderColumn], $orderDirection);
                } else {
                    // fallback: assume it's a column in 'units' or your main table
                    $model->orderBy($orderColumn, $orderDirection);
                }
            } else {
                // Default order if column is non-sortable or invalid
                $model->orderBy('offices.created_at', 'desc');
            }
        } else {
            // Default order if no order parameter is sent
            $model->orderBy('offices.created_at', 'desc');
        }

        if ($request->ajax()) {
            return DataTables::eloquent($model)
                ->addIndexColumn()
                ->addColumn('checkbox', function ($office) {
                    return '<input type="checkbox" class="office-checkbox" value="' . (int) $office->id . '" id="office_' . (int) $office->id . '">';
                })
                ->addColumn('office_name', function ($office) {
                    $output = $office->formatted_office_name;

                    if ($office->office_website) {
                        $output .= '<br><a href="' . $office->office_website . '" target="_blank" class="text-info fs-24">
                        <iconify-icon icon="solar:square-arrow-right-up-bold" class="text-info fs-24"></iconify-icon>
                    </a>';
                    }

                    return $output;
                })
                ->editColumn('office_postcode', function ($office) {
                    $rawPostcode = trim($office->formatted_postcode);
                    if (empty($rawPostcode)) {
                        return '<div class="text-center w-100">-</div>';
                    }

                    $postcode = $office->formatted_postcode;
                    $copyBtn = '<button type="button" class="btn btn-sm btn-link text-muted p-0 ms-2 copy-postcode" 
                                    data-postcode="' . e($office->formatted_postcode) . '" title="Copy Postcode">
                                    <iconify-icon icon="solar:copy-linear" class="fs-18"></iconify-icon>
                                </button>';

                    if ($office->office_lat != null && $office->office_lng != null) {
                        $url = route('applicants.available_job', ['id' => $office->id, 'radius' => 15]);
                        $link = '<a href="' . $url . '" target="_blank" class="active_postcode">' . $postcode . '</a>';

                        return '<div class="d-flex align-items-center justify-content-between">' . $link . $copyBtn . '</div>';
                    } else {
                        return '<div class="d-flex align-items-center justify-content-between"><span>' . $postcode . '</span>' . $copyBtn . '</div>';
                    }
                })
                ->addColumn('office_type', function ($office) {
                    return ucwords(str_replace('_', ' ', $office->office_type));
                })
                ->addColumn('contact_email', function ($office) {
                    return $office->contact->pluck('contact_email')->filter()->implode('<br>') ?: '-';
                })
                ->addColumn('contact_landline', function ($office) {
                    return $office->contact->pluck('contact_landline')->filter()->implode('<br>') ?: '-';
                })
                ->addColumn('contact_phone', function ($office) {
                    return $office->contact->pluck('contact_phone')->filter()->implode('<br>') ?: '-';
                })
                ->filterColumn('contact_email', function ($query, $keyword) {
                    $query->whereRaw('LOWER(contacts.contact_email) LIKE ?', ['%' . strtolower(trim($keyword)) . '%']);
                })
                ->filterColumn('contact_phone', function ($query, $keyword) {
                    $query->whereRaw('contacts.contact_phone LIKE ?', ['%' . trim($keyword) . '%']);
                })
                ->filterColumn('contact_landline', function ($query, $keyword) {
                    $query->whereRaw('contacts.contact_landline LIKE ?', ['%' . trim($keyword) . '%']);
                })
                ->filterColumn('office_notes', function ($query, $keyword) {
                    $query->whereRaw('LOWER(offices.office_notes) LIKE ?', ['%' . strtolower(trim($keyword)) . '%']);
                })
                ->orderColumn('contact_email', function ($query, $order) {
                    $query->orderBy('contacts.contact_email', $order);
                })
                ->orderColumn('contact_phone', function ($query, $order) {
                    $query->orderBy('contacts.contact_phone', $order);
                })
                ->orderColumn('contact_landline', function ($query, $order) {
                    $query->orderBy('contacts.contact_landline', $order);
                })
                ->addColumn('updated_at', function ($office) {
                    return $office->formatted_updated_at;
                })
                ->addColumn('created_at', function ($office) {
                    return $office->formatted_created_at;
                })
                ->editColumn('office_notes', function ($office) {
                    $notes = nl2br(htmlspecialchars($office->office_notes ?? '', ENT_QUOTES, 'UTF-8'));

                    return '<a href="javascript:void(0);" title="Add Short Note" style="color:blue" onclick="addShortNotesModal(\'' . (int) $office->id . '\')">' . $notes . '</a>';
                })
                ->addColumn('action', function ($office) {
                    $postcode = $office->formatted_postcode;

                    $status = '';
                    if ($office->status == 1) {
                        $status .= '<span class="badge bg-success">Active</span>';
                    } elseif ($office->status == 0) {
                        $status .= '<span class="badge bg-dark">Disabled</span>';
                    } elseif ($office->status == 4) {
                        $status .= '<span class="badge bg-primary">Scrapped</span>';
                    }

                    $html = '<div class="btn-group dropstart">
                                <button type="button" class="border-0 bg-transparent p-0" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    <iconify-icon icon="solar:menu-dots-square-outline" class="align-middle fs-24 text-dark"></iconify-icon>
                                </button>
                                <ul class="dropdown-menu">';
                    if (Gate::allows('office-edit')) {
                        $html .= '<li><a class="dropdown-item" href="' . route('head-offices.edit', ['id' => $office->id, 'redirect_url' => route('scrap.office.list')]) . '">Edit</a></li>';
                    }
                    if (Gate::allows('office-view')) {
                        $html .= '<li><a class="dropdown-item" href="javascript:void(0);" onclick="showDetailsModal(' . (int) $office->id . ',\'' . addslashes(htmlspecialchars($office->office_name)) . '\',\'' . addslashes(htmlspecialchars($postcode)) . '\',\'' . addslashes(htmlspecialchars($status)) . '\')">View</a></li>';
                    }
                    if (Gate::allows('office-view-notes-history') || Gate::allows('office-view-manager-details')) {
                        $html .= '<li><hr class="dropdown-divider"></li>';
                    }
                    if (Gate::allows('office-view-notes-history')) {
                        $html .= '<li><a class="dropdown-item" href="javascript:void(0);" onclick="viewNotesHistory(' . $office->id . ')">Notes History</a></li>';
                    }
                    if (Gate::allows('office-view-manager-details')) {
                        $html .= '<li><a class="dropdown-item" href="javascript:void(0);" onclick="viewManagerDetails(' . $office->id . ')">Manager Details</a></li>';
                    }
                    if ($office->deleted_at != null) {
                        $html .= '<li><a class="dropdown-item" href="javascript:void(0);" onclick="restoreOffice(' . $office->id . ')">Restore</a></li>';
                    } else {
                        $html .= '<li><a class="dropdown-item" href="javascript:void(0);" onclick="deleteOffice(' . $office->id . ')">Delete</a></li>';
                    }

                    $html .= '</ul></div>';

                    return $html;
                })
                ->rawColumns(['checkbox', 'office_name', 'office_notes', 'contact_email', 'office_postcode', 'contact_phone', 'contact_landline', 'office_type', 'action'])
                ->toJson();
        }
    }

    public function getScrappedUnits(Request $request)
    {
        $statusFilter = $request->input('status_filter');
        $officeFilter = $request->input('office_filter', ''); // Default is empty (no filter)

        $query = Unit::withTrashed()
            ->select('units.*', 'offices.office_name as office_name', 'unit_contacts.contact_email as contact_email', 'unit_contacts.contact_phone as contact_phone', 'unit_contacts.contact_landline as contact_landline')
            ->leftJoin('offices', 'units.office_id', '=', 'offices.id')
            ->leftJoinSub(
                DB::table('contacts')
                    ->select([
                        'contactable_id',
                        DB::raw('GROUP_CONCAT(DISTINCT contact_email SEPARATOR ", ") as contact_email'),
                        DB::raw('GROUP_CONCAT(DISTINCT contact_phone SEPARATOR ", ") as contact_phone'),
                        DB::raw('GROUP_CONCAT(DISTINCT contact_landline SEPARATOR ", ") as contact_landline'),
                    ])
                    ->where('contactable_type', 'Horsefly\\Unit')
                    ->groupBy('contactable_id'),
                'unit_contacts',
                'unit_contacts.contactable_id',
                '=',
                'units.id'
            )
            ->where('units.status', 4); // scrapped

        // Office filter
        if ($officeFilter !== '') {
            $query->whereIn('units.office_id', $officeFilter);
        }

        if ($statusFilter === 'deleted') {
            $query->where('offices.status', 4)
                ->onlyTrashed(); // 👈 BEST & CLEAN
        } else {
            $query->where('offices.status', 4)
                ->whereNull('offices.deleted_at');
        }

        // ─── Turbo Search Optimization (Units + Contacts) ───────────────────
        if ($request->filled('search.value')) {
            $search = trim($request->input('search.value'));

            if (strlen($search) >= 2) {
                // 1. Scout search matches unit_name, etc.
                $unitIdsFromElastic = Unit::search($search)->keys()->toArray();

                // 2. Search for Units via Office (Scout search on Office model)
                $officeIds = Office::search($search)->keys()->toArray();
                $unitIdsByOffice = Unit::whereIn('office_id', $officeIds)->pluck('id')->toArray();

                // 3. Still do the Contact SQL check
                $contactIds = Contact::where('contactable_id', '>', 0)
                    ->where('contactable_type', 'Horsefly\\Unit')
                    ->where(function ($q) use ($search) {
                        $q->where('contact_email', 'LIKE', "%{$search}%")
                            ->orWhere('contact_phone', 'LIKE', "%{$search}%");
                    })->pluck('contactable_id')->toArray();

                $allIds = array_unique(array_merge($unitIdsFromElastic, $unitIdsByOffice, $contactIds));

                $query->whereIn('units.id', $allIds);
            }
        }

        // Sorting logic
        if ($request->has('order')) {
            $orderColumnIndex = $request->input('order.0.column');
            $orderColumn = $request->input("columns.$orderColumnIndex.data");
            $orderDirection = $request->input('order.0.dir', 'asc');

            // List of columns that are not actual database columns and should be skipped
            $nonSortableColumns = [
                'checkbox',
                'action',
                // add other non-database columns here if needed
            ];

            if ($orderColumn && $orderColumn !== 'DT_RowIndex' && ! in_array($orderColumn, $nonSortableColumns)) {
                // Map the column if needed, or directly use the column name
                // Example: if you want to map 'office_name' to 'offices.name', do it here
                $columnMap = [
                    'office_name' => 'offices.office_name',
                    'unit_name' => 'units.unit_name',
                    'unit_postcode' => 'units.unit_postcode',
                    'contact_email' => 'unit_contacts.contact_email',
                    'contact_phone' => 'unit_contacts.contact_phone',
                    'contact_landline' => 'unit_contacts.contact_landline',
                    'unit_notes' => 'units.unit_notes',
                    'created_at' => 'units.created_at',
                ];

                if (isset($columnMap[$orderColumn])) {
                    $query->orderBy($columnMap[$orderColumn], $orderDirection);
                } else {
                    // fallback: assume it's a column in 'units' or your main table
                    $query->orderBy($orderColumn, $orderDirection);
                }
            } else {
                // Default order if column is non-sortable or invalid
                $query->orderBy('units.created_at', 'desc');
            }
        } else {
            // Default order if no order parameter is sent
            $query->orderBy('units.created_at', 'desc');
        }

        /* -------------------------------------------------
        | DataTables Response
        -------------------------------------------------*/
        return DataTables::eloquent($query)
            ->addIndexColumn()
            ->addColumn('checkbox', function ($u) {
                return '<input type="checkbox" class="unit-checkbox" value="' . (int) $u->id . '" id="unit_' . (int) $u->id . '">';
            })
            ->addColumn('office_name', fn($u) => $u->office?->office_name ?? '-')
            ->addColumn('office_name', function ($u) {
                $output = $u->office?->formatted_office_name;

                if ($u->office?->office_website) {
                    $output .= '<br><a href="' . $u->office->office_website . '" target="_blank" class="text-info fs-24">
                    <iconify-icon icon="solar:square-arrow-right-up-bold" class="text-info fs-24"></iconify-icon>
                    </a>';
                }

                return $output;
            })
            ->filterColumn('office_name', function ($query, $keyword) {
                $words = preg_split('/\s+/', $keyword, -1, PREG_SPLIT_NO_EMPTY);
                foreach ($words as $word) {
                    $query->where('offices.office_name', 'LIKE', "%{$word}%");
                }
            })
            ->addColumn('unit_name', function ($u) {
                $output = $u->formatted_unit_name;

                if ($u->unit_website) {
                    $output .= '<br><a href="' . $u->unit_website . '" target="_blank" class="text-info fs-24">
                    <iconify-icon icon="solar:square-arrow-right-up-bold" class="text-info fs-24"></iconify-icon>
                    </a>';
                }

                return $output;
            })
            ->addColumn('unit_postcode', fn($u) => $u->formatted_postcode)
            ->editColumn('unit_postcode', function ($u) {
                $rawPostcode = trim($u->formatted_postcode);
                if (empty($rawPostcode)) {
                    return '<div class="text-center w-100">-</div>';
                }

                $postcode = $u->formatted_postcode;
                $copyBtn = '<button type="button" class="btn btn-sm btn-link text-muted p-0 ms-2 copy-postcode" 
                                data-postcode="' . e($u->formatted_postcode) . '" title="Copy Postcode">
                                <iconify-icon icon="solar:copy-linear" class="fs-18"></iconify-icon>
                            </button>';

                return '<div class="d-flex align-items-center justify-content-between">' . $postcode . $copyBtn . '</div>';
            })
            ->addColumn(
                'contact_email',
                fn($u) => $u->contacts->pluck('contact_email')->filter()->implode('<br>') ?: '-'
            )
            ->addColumn(
                'contact_phone',
                fn($u) => $u->contacts->pluck('contact_phone')->filter()->implode('<br>') ?: '-'
            )
            ->addColumn(
                'contact_landline',
                fn($u) => $u->contacts->pluck('contact_landline')->filter()->implode('<br>') ?: '-'
            )
            ->filterColumn('contact_email', function ($query, $keyword) {
                $keyword = trim($keyword);
                $query->whereExists(function ($q) use ($keyword) {
                    $q->select(DB::raw(1))
                        ->from('contacts')
                        ->whereColumn('contacts.contactable_id', 'units.id')
                        ->where('contacts.contactable_type', Unit::class)
                        ->where('contact_email', 'LIKE', "{$keyword}%");
                });
            })
            ->filterColumn('contact_phone', function ($query, $keyword) {
                $clean = preg_replace('/[^0-9]/', '', $keyword);
                $query->whereExists(function ($q) use ($clean) {
                    $q->select(DB::raw(1))
                        ->from('contacts')
                        ->whereColumn('contacts.contactable_id', 'units.id')
                        ->where('contacts.contactable_type', Unit::class)
                        ->where('contact_phone', 'LIKE', "{$clean}%");
                });
            })
            ->filterColumn('contact_landline', function ($query, $keyword) {
                $clean = preg_replace('/[^0-9]/', '', $keyword);
                $query->whereExists(function ($q) use ($clean) {
                    $q->select(DB::raw(1))
                        ->from('contacts')
                        ->whereColumn('contacts.contactable_id', 'units.id')
                        ->where('contacts.contactable_type', Unit::class)
                        ->where('contact_landline', 'LIKE', "{$clean}%");
                });
            })

            ->addColumn('created_at', fn($u) => $u->formatted_created_at)
            ->addColumn('updated_at', fn($u) => $u->formatted_updated_at)
            ->addColumn(
                'unit_notes',
                fn($u) => '<a href="javascript:void(0);" onclick="addShortNotesModal(' . (int) $u->id . ')">'
                    . nl2br(e($u->unit_notes)) . '</a>'
            )
            ->addColumn('action', function ($u) {
                $postcode = $u->formatted_postcode;
                $office_name = $u->office?->office_name ?? '-';
                $status = '';
                if ($u->status == 1) {
                    $status .= '<span class="badge bg-success">Active</span>';
                } elseif ($u->status == 0) {
                    $status .= '<span class="badge bg-dark">Disabled</span>';
                } elseif ($u->status == 4) {
                    $status .= '<span class="badge bg-primary">Scrapped</span>';
                }

                $html = '<div class="btn-group dropstart">
                        <button type="button" class="border-0 bg-transparent p-0" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <iconify-icon icon="solar:menu-dots-square-outline" class="align-middle fs-24 text-dark"></iconify-icon>
                        </button>
                        <ul class="dropdown-menu">';

                if (Gate::allows('unit-edit')) {
                    $html .= '<li><a class="dropdown-item" href="' . route('units.edit', ['id' => $u->id, 'redirect_url' => route('scrap.unit.list')]) . '">Edit</a></li>';
                }
                if (Gate::allows('unit-view')) {
                    $html .= '<li><a class="dropdown-item"href="javascript:void(0);" onclick="showDetailsModal('
                        . (int) $u->id . ', '
                        . '\'' . e($office_name) . '\', '
                        . '\'' . e($u->unit_name) . '\', '
                        . '\'' . e($postcode) . '\', '
                        . '\'' . e($status) . '\')">View</a></li>';
                }
                if (Gate::allows('unit-view-notes-history') || Gate::allows('unit-view-manager-details')) {
                    $html .= '<li><hr class="dropdown-divider"></li>';
                }
                if (Gate::allows('unit-view-notes-history')) {
                    $html .= '<li><a class="dropdown-item"href="javascript:void(0);" onclick="viewNotesHistory(' . $u->id . ')">Notes History</a></li>';
                }
                if (Gate::allows('unit-view-manager-details')) {
                    $html .= '<li><a class="dropdown-item"href="javascript:void(0);" onclick="viewManagerDetails(' . $u->id . ')">Manager Details</a></li>';
                }
                if ($u->deleted_at != null) {
                    $html .= '<li><a class="dropdown-item" href="javascript:void(0);" onclick="restoreUnit(' . $u->id . ')">Restore</a></li>';
                } else {
                    $html .= '<li><a class="dropdown-item" href="javascript:void(0);" onclick="deleteUnit(' . $u->id . ')">Delete</a></li>';
                }

                $html .= '</ul></div>';

                return $html;
            })

            ->rawColumns([
                'checkbox',
                'unit_notes',
                'contact_email',
                'contact_phone',
                'contact_landline',
                'office_name',
                'unit_name',
                'action',
                'unit_postcode',
            ])
            ->make(true);
    }

    private function formatWithUrlCTA(string $fullHtml, string $idPrefix, int $saleId, string $modalTitle)
    {

        // 0. Remove inline styles and <span> tags (to avoid affecting layout)
        $cleanedHtml = preg_replace('/<(span|[^>]+) style="[^"]*"[^>]*>/i', '<$1>', $fullHtml);
        $cleanedHtml = preg_replace('/<\/?span[^>]*>/i', '', $cleanedHtml);

        // 1. Convert block-level and <br> tags into \n
        $withBreaks = preg_replace(
            '/<(\/?(p|div|li|br|ul|ol|tr|td|table|h[1-6]))[^>]*>/i',
            "\n",
            $cleanedHtml
        );

        // 2. Remove all other HTML tags except basic formatting tags
        $plainText = strip_tags($withBreaks, '<b><strong><i><em><u>');

        // 3. Decode HTML entities
        $decodedText = html_entity_decode($plainText);

        // 4. Normalize multiple newlines
        $normalizedText = preg_replace("/[\r\n]+/", "\n", $decodedText);

        // 5. Detect URL in the plain text
        preg_match('/https?:\/\/[^\s]+/', $normalizedText, $matches);
        $url = $matches[0] ?? null;

        // 6. Remove the URL from the text if present to avoid showing long links in preview
        $textForPreview = $url ? str_replace($url, '', $normalizedText) : $normalizedText;

        // 7. Limit preview characters
        $preview = Str::limit(trim($textForPreview), 80);

        // 8. Convert newlines to <br>
        $shortText = nl2br($preview);

        $id = $idPrefix . '-' . $saleId;

        $urlCTA = '';
        $modalBody = $fullHtml;
        if ($url) {
            $urlCTA = '<a href="' . $url . '" target="_blank" class="btn btn-xs btn-info rounded-pill px-2 ms-1" title="Open Link">
                        <iconify-icon icon="mdi:link-variant"></iconify-icon> URL
                       </a>';

            // Generate a larger CTA button for the modal view
            $modalCTA = '<div class="my-2"><a href="' . $url . '" target="_blank" class="btn btn-sm btn-info rounded-pill px-3 py-1 d-inline-flex align-items-center shadow-sm" title="Open Link">
                            <iconify-icon icon="mdi:link-variant" class="me-2"></iconify-icon> Click to Open Link
                         </a></div>';
            $modalBody = str_replace($url, $modalCTA, $fullHtml);
        }

        return '<div class="d-flex flex-column align-items-start">
                    <a href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#' . $id . '">' . $shortText . '</a>' . $urlCTA . '
                </div>
                <div class="modal fade" id="' . $id . '" tabindex="-1" aria-labelledby="' . $id . '-label" aria-hidden="true">
                    <div class="modal-dialog modal-lg modal-dialog-scrollable">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="' . $id . '-label">' . $modalTitle . '</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                ' . $modalBody . '
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-dark" data-bs-dismiss="modal">Close</button>
                            </div>
                        </div>
                    </div>
                </div>';
    }

    public function getScrappedSales(Request $request)
    {
        $statusFilter = $request->input('status_filter', ''); // Default is empty (no filter)
        $typeFilter = $request->input('type_filter', ''); // Default is empty (no filter)
        $categoryFilter = $request->input('category_filter', ''); // Default is empty (no filter)
        $titleFilter = $request->input('title_filter', ''); // Default is empty (no filter)
        $limitCountFilter = $request->input('cv_limit_filter', ''); // Default is empty (no filter)
        $officeFilter = $request->input('office_filter', ''); // Default is empty (no filter)
        $userFilter = $request->input('user_filter', ''); // Default is empty (no filter)
        $sourceFilter = $request->input('source_filter', ''); // Default is empty (no filter)

        // 🚀 OPTIMIZED: Fetch only essential columns for list view
        // Removed expensive JOINs: latest_notes, office_contacts, audits
        // These can be lazy-loaded or fetched on-demand
        $model = Sale::withTrashed()
            ->select([
                'sales.id',
                'sales.sale_uid',
                'sales.office_id',
                'sales.unit_id',
                'sales.user_id',
                'sales.job_category_id',
                'sales.job_title_id',
                'sales.job_type',
                'sales.position_type',
                'sales.sale_postcode',
                'sales.qualification',
                'sales.salary',
                'sales.experience',
                'sales.cv_limit',
                'sales.timing',
                'sales.status',
                'sales.is_on_hold',
                'sales.is_re_open',
                'sales.lat',
                'sales.lng',
                'sales.sale_notes',
                'sales.created_at',
                'sales.updated_at',
                'sales.deleted_at',
            ])
            ->leftJoin('job_titles', 'sales.job_title_id', '=', 'job_titles.id')
            ->leftJoin('job_categories', 'sales.job_category_id', '=', 'job_categories.id')
            ->leftJoin('offices', 'sales.office_id', '=', 'offices.id')
            ->leftJoin('units', 'sales.unit_id', '=', 'units.id')
            ->leftJoin('users', 'sales.user_id', '=', 'users.id')
            ->leftJoinSub(
                DB::table('contacts')
                    ->select([
                        'contactable_id',
                        DB::raw('GROUP_CONCAT(DISTINCT contact_email SEPARATOR ", ") as office_emails'),
                        DB::raw('GROUP_CONCAT(DISTINCT contact_phone SEPARATOR ", ") as office_phones'),
                    ])
                    ->where('contactable_type', 'Horsefly\\Office')
                    ->groupBy('contactable_id'),
                'office_contacts',
                'office_contacts.contactable_id',
                '=',
                'offices.id'
            )
            ->addSelect(
                'job_titles.name as job_title_name',
                'job_categories.name as job_category_name',
                'offices.office_name as office_name',
                'offices.office_website as office_website',
                'units.unit_website as unit_website',
                'units.unit_name as unit_name',
                'users.name as user_name',
                'office_contacts.office_emails as office_emails',
                'office_contacts.office_phones as office_phones'
            );

        if ($statusFilter === 'deleted') {
            $model->where('sales.status', 4)
                ->onlyTrashed(); // 👈 BEST & CLEAN
        } else {
            $model->where('sales.status', 4)
                ->whereNull('sales.deleted_at');
        }

        if ($request->filled('search.value')) {
            $searchTerm = (string) $request->input('search.value');

            // 1. Get Matching IDs from Scout (searches internal Sale columns like postcode, UID, etc.)
            $saleIds = Sale::search($searchTerm)->keys()->toArray();

            // 2. Combine Scout results with direct relationship searches
            $model->where(function ($query) use ($searchTerm, $saleIds) {
                // IDs from Scout
                if (! empty($saleIds)) {
                    $query->whereIn('sales.id', $saleIds);
                }

                // Plus manual searches for relationships (Scout's database driver doesn't JOIN)
                $query->orWhere('offices.office_name', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('units.unit_name', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('job_titles.name', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('job_categories.name', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('users.name', 'LIKE', "%{$searchTerm}%");
            });
        }

        // Filter by type if it's not empty
        if ($typeFilter == 'specialist') {
            $model->where('sales.job_type', 'specialist');
        } elseif ($typeFilter == 'regular') {
            $model->where('sales.job_type', 'regular');
        }

        // Filter by category if it's not empty
        if ($officeFilter) {
            $model->whereIn('sales.office_id', $officeFilter);
        }

        // Filter by source if it's not empty
        if ($sourceFilter) {
            $model->whereIn('sales.job_source_id', $sourceFilter);
        }

        // Filter by category if it's not empty
        if ($categoryFilter) {
            $model->whereIn('sales.job_category_id', $categoryFilter);
        }

        // Filter by category if it's not empty
        if ($titleFilter) {
            $model->whereIn('sales.job_title_id', $titleFilter);
        }

        // Filter by user if it's not empty
        if ($userFilter) {
            $model->whereIn('sales.user_id', $userFilter);
        }

        // ------------------------------------------------------
        // ✅ SAFE SORTING (Handles aliases + checkbox + computed)
        // ------------------------------------------------------
        if ($request->has('order')) {

            $columnIndex = $request->input('order.0.column');
            $orderDirection = $request->input('order.0.dir', 'asc');
            $orderColumn = $request->input("columns.$columnIndex.data");

            // Map DataTable columns → actual DB columns
            $columnMap = [
                'office_name' => 'offices.office_name',
                'office_emails' => 'office_contacts.office_emails',
                'office_phones' => 'office_contacts.office_phones',
                'unit_name' => 'units.unit_name',
                'job_title' => 'job_titles.name',
                'job_category' => 'job_categories.name',
                'open_date' => 'sales.created_at',
                'created_at' => 'sales.created_at',
                'updated_at' => 'sales.updated_at',
            ];

            // ❌ Skip non-sortable columns
            $nonSortable = [
                'checkbox',
                'action',
                'sale_notes',
                'cv_limit',
                'position_type',
            ];

            if (in_array($orderColumn, $nonSortable)) {
                $model->orderBy('sales.updated_at', 'desc');
            } elseif (isset($columnMap[$orderColumn])) {
                $model->orderBy($columnMap[$orderColumn], $orderDirection);
            } elseif (! empty($orderColumn) && $orderColumn !== 'DT_RowIndex') {
                $model->orderBy("sales.$orderColumn", $orderDirection);
            } else {
                $model->orderBy('sales.updated_at', 'desc');
            }
        } else {
            // Default sorting
            $model->orderBy('sales.updated_at', 'desc');
        }

        if ($request->ajax()) {
            return DataTables::eloquent($model)
                ->addIndexColumn() // This will automatically add a serial number to the rows
                ->addColumn('checkbox', function ($sale) {
                    return '<input type="checkbox" class="sale-checkbox" value="' . (int) $sale->id . '" id="sale_' . (int) $sale->id . '">';
                })
                ->addColumn('office_name', function ($sale) {
                    $output = $sale->office_name ? ucwords($sale->office_name) : '-';

                    if ($sale->office_website) {
                        $output .= '<br><a href="' . $sale->office_website . '" target="_blank" class="text-info fs-24">
                        <iconify-icon icon="solar:square-arrow-right-up-bold" class="text-info fs-24"></iconify-icon>
                        </a>';
                    }

                    return $output;
                })
                ->addColumn('office_emails', function ($sale) {
                    if (!$sale->office_emails) return '-';
                    $emails = explode(', ', $sale->office_emails);
                    return implode('<br>', array_map('htmlspecialchars', $emails));
                })
                ->addColumn('office_phones', function ($sale) {
                    if (!$sale->office_phones) return '-';
                    $phones = explode(', ', $sale->office_phones);
                    return implode('<br>', array_map('htmlspecialchars', $phones));
                })
                ->addColumn('unit_name', function ($sale) {
                    $output = $sale->unit_name ? ucwords($sale->unit_name) : '-';

                    if ($sale->unit_website) {
                        $output .= '<br><a href="' . $sale->unit_website . '" target="_blank" class="text-info fs-24">
                        <iconify-icon icon="solar:square-arrow-right-up-bold" class="text-info fs-24"></iconify-icon>
                        </a>';
                    }

                    return $output;
                })
                ->addColumn('job_title', function ($sale) {
                    return $sale->job_title_name ? strtoupper($sale->job_title_name) : '-';
                })
                ->addColumn('open_date', function ($sale) {
                    return $sale->open_date ? Carbon::parse($sale->open_date)->format('d M Y, h:i A') : '-';
                })
                ->addColumn('job_category', function ($sale) {
                    $stype = $sale->job_type == 'specialist' ? '<br>(Specialist)' : '';

                    return $sale->job_category_name ? ucwords($sale->job_category_name) . $stype : '-';
                })
                ->addColumn('sale_postcode', function ($sale) {
                    $copyBtn = '<button type="button" class="btn btn-sm btn-link text-muted p-0 ms-2 copy-postcode" 
                                    data-postcode="' . e($sale->formatted_postcode) . '" title="Copy Postcode">
                                    <iconify-icon icon="solar:copy-linear" class="fs-18"></iconify-icon>
                                </button>';

                    if ($sale->lat != null && $sale->lng != null) {
                        $url = url('/sales/fetch-applicants-by-radius/' . $sale->id . '/15');
                        $button = '<a target="_blank" href="' . $url . '" class="active_postcode">' . $sale->formatted_postcode . '</a>'; // Using accessor

                        return '<div class="d-flex align-items-center justify-content-between">' . $button . $copyBtn . '</div>';
                    } else {
                        return '<div class="d-flex align-items-center justify-content-between"><span>' . $sale->formatted_postcode . '</span>' . $copyBtn . '</div>';
                    }
                })
                ->addColumn('qualification', function ($sale) {
                    return $this->formatWithUrlCTA($sale->qualification, 'qua', $sale->id, 'Sale Qualification');
                })
                ->addColumn('experience', function ($sale) {
                    return $this->formatWithUrlCTA($sale->experience, 'exp', $sale->id, 'Sale Experience');
                })
                ->addColumn('salary', function ($sale) {
                    return $this->formatWithUrlCTA($sale->salary, 'slry', $sale->id, 'Sale`s Salary');
                })
                ->addColumn('created_at', function ($sale) {
                    return $sale->formatted_created_at; // Using accessor
                })
                ->addColumn('updated_at', function ($sale) {
                    return $sale->formatted_updated_at; // Using accessor
                })
                ->addColumn('cv_limit', function ($sale) {
                    $status = $sale->no_of_sent_cv == $sale->cv_limit ? '<span class="badge w-100 bg-danger" style="font-size:90%" >0/' . $sale->cv_limit . '<br>Limit Reached</span>' : "<span class='badge w-100 bg-primary' style='font-size:90%'>" . ((int) $sale->cv_limit - (int) $sale->no_of_sent_cv . '/' . (int) $sale->cv_limit) . '<br>Limit Remains</span>';

                    return $status;
                })
                ->addColumn('position_type', function ($sale) {
                    if (empty($sale->position_type)) {
                        return '-';
                    }

                    $colors = [
                        'full time' => 'bg-primary',
                        'part time' => 'bg-info',
                        'permanent' => 'bg-success',
                        'temporary' => 'bg-warning',
                    ];

                    $types = array_filter(array_map('trim', explode(',', $sale->position_type)));

                    $badges = '';
                    foreach ($types as $type) {
                        $key = strtolower(str_replace(' ', '-', $type));
                        $color = $colors[$key] ?? 'bg-secondary'; // fallback color
                        $label = ucwords(str_replace('-', ' ', strtolower($type)));
                        $badges .= "<span class='badge {$color} me-1'>{$label}</span>";
                    }

                    return $badges;
                })
                ->addColumn('sale_notes', function ($sale) {
                    $notesIndex = ! empty($sale->sale_notes) ? $sale->sale_notes : '-';

                    preg_match('/https?:\/\/[^\s]+/', $notesIndex, $matches);
                    $url = $matches[0] ?? null;

                    $notesValue = $url ? str_replace($url, '', $notesIndex) : $notesIndex;
                    $shortNotes = Str::limit(trim(strip_tags($notesValue)), 80);

                    $urlCTA = '';
                    $escapedNotes = htmlspecialchars($notesIndex, ENT_QUOTES, 'UTF-8');
                    if ($url) {
                        $urlCTA = '<a href="' . $url . '" target="_blank" class="btn btn-xs btn-info rounded-pill px-2 ms-1" title="Open Link">
                                                    <iconify-icon icon="mdi:link-variant"></iconify-icon> URL
                                            </a>';
                    }

                    $notes = nl2br($escapedNotes);
                    $postcode = htmlspecialchars($sale->sale_postcode, ENT_QUOTES, 'UTF-8');
                    $office_name = ucwords($sale->office_name ?? '-');
                    $unit_name = ucwords($sale->unit_name ?? '-');

                    return '<div class="d-flex flex-column align-items-start">
                                    <a href="javascript:void(0);" title="View Note" onclick="showNotesModal(\'' . (int) $sale->id . '\',\'' . $notes . '\', \'' . $office_name . '\', \'' . $unit_name . '\', \'' . $postcode . '\')">
                                        ' . $shortNotes . '
                                    </a>
                                </div>' . $urlCTA . '
                            </div>';
                })
                ->addColumn('action', function ($sale) {
                    $postcode = strtoupper($sale->sale_postcode ?? '-');
                    $posted_date = $sale->formatted_created_at;
                    $office_name = ucwords($sale->office_name ?? '-');
                    $unit_name = ucwords($sale->unit_name ?? '-');
                    $jobTitle = strtoupper($sale->job_title_name ?? '-');
                    $stype = $sale->job_type == 'specialist' ? ' (Specialist)' : '';
                    $jobCategory = ucwords(($sale->job_category_name ?? '-') . $stype);

                    // Status badge
                    $status_badge = '';
                    if ($sale->status == 1 && $sale->is_on_hold == 1) {
                        $status_badge = '<span class="badge bg-warning">On Hold</span>';
                    } elseif ($sale->status == 1 && $sale->is_re_open == 1) {
                        $status_badge = '<span class="badge bg-dark">Re-Open</span>';
                    } elseif ($sale->status == 0) {
                        $status_badge = '<span class="badge bg-danger">Closed</span>';
                    } elseif ($sale->status == 1) {
                        $status_badge = '<span class="badge bg-success">Open</span>';
                    } elseif ($sale->status == 2) {
                        $status_badge = '<span class="badge bg-warning">Pending</span>';
                    } elseif ($sale->status == 3) {
                        $status_badge = '<span class="badge bg-danger">Rejected</span>';
                    } elseif ($sale->status == 4) {
                        $status_badge = '<span class="badge bg-secondary">Scrapped</span>';
                    }

                    $pos = strtoupper(str_replace('-', ' ', $sale->position_type ?? ''));
                    $position = '<span class="badge bg-primary">' . e($pos) . '</span>';

                    $action = '';
                    $action .= '<div class="btn-group dropstart">
                                    <button type="button" class="border-0 bg-transparent p-0" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    <iconify-icon icon="solar:menu-dots-square-outline" class="align-middle fs-24 text-dark"></iconify-icon>
                                </button>
                                    <ul class="dropdown-menu">';

                    if (Gate::allows('sale-edit')) {
                        $action .= '<li><a class="dropdown-item" href="' . route('sales.edit', ['id' => (int) $sale->id, 'redirect_url' => route('scrap.sales.list')]) . '">Edit</a></li>';
                    }

                    if (Gate::allows('sale-view')) {
                        $experience = htmlspecialchars($sale->experience, ENT_QUOTES, 'UTF-8'); // ← move it outside
                        $action .= '<li><a class="dropdown-item" href="javascript:void(0);" onclick="showDetailsModal(
                            ' . $sale->id . ',
                            \'' . e($posted_date) . '\',
                            \'' . e($office_name) . '\',
                            \'' . e($unit_name) . '\',
                            \'' . e($postcode) . '\',
                            \'' . e(strip_tags($jobCategory)) . '\',
                            \'' . e(strip_tags($jobTitle)) . '\',
                            \'' . e($status_badge) . '\',
                            \'' . e($sale->timing) . '\',
                            \'' . e($experience) . '\',
                            \'' . e($sale->salary) . '\',
                            \'' . e(strip_tags($position)) . '\',
                            \'' . e($sale->qualification) . '\',
                            \'' . e($sale->benefits) . '\'
                        )">View</a></li>';
                    }

                    if (Gate::allows('sale-add-note')) {
                        $action .= '<li><a class="dropdown-item" href="javascript:void(0);" onclick="addNotesModal(' . (int) $sale->id . ')">Add Note</a></li>';
                    }

                    $action .= '<li>
                                <a class="dropdown-item" href="javascript:void(0);" onclick="openEmailModal(' . (int) $sale->id . ')">
                                    Send Email
                                </a>
                            </li>';

                    $action .= '<li><hr class="dropdown-divider"></li>';

                    if (Gate::allows('sale-view-documents')) {
                        $action .= '<li><a class="dropdown-item" href="javascript:void(0);" onclick="viewSaleDocuments(' . (int) $sale->id . ')">View Documents</a></li>';
                    }

                    $action .= '<li><a class="dropdown-item"href="javascript:void(0);" onclick="viewNotesHistory(' . $sale->id . ')">Notes History</a></li>';

                    if (Gate::allows('sale-view-manager-details')) {
                        $action .= '<li><a class="dropdown-item" href="javascript:void(0);" onclick="viewManagerDetails(' . (int) $sale->office_id . ')">Manager Details</a></li>';
                    }

                    if ($sale->deleted_at != null) {
                        $action .= '<li><a class="dropdown-item" href="javascript:void(0);" onclick="restoreSale(' . $sale->id . ')">Restore</a></li>';
                    } else {
                        $action .= '<li><a class="dropdown-item" href="javascript:void(0);" onclick="deleteSale(' . $sale->id . ')">Delete</a></li>';
                    }

                    $action .= '</ul></div>';

                    return $action;
                })
                ->rawColumns(['checkbox', 'sale_notes', 'experience', 'office_emails', 'office_phones', 'position_type', 'sale_postcode', 'qualification', 'job_title', 'cv_limit', 'open_date', 'job_category', 'office_name', 'salary', 'unit_name', 'action', 'statusFilter'])
                ->make(true);
        }
    }

    // scrap destroy
    public function scrappedOfficeDestroy(Request $request)
    {
        $user = Auth::user();
        try {
            $ids = $request->has('id')
                ? (is_array($request->id) ? $request->id : [$request->id])
                : [];

            $offices = Office::whereIn('id', $ids)->where('status', 4)->get();

            if ($offices->isEmpty()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Office(s) not found',
                ], 404);
            }

            $foundIds = $offices->pluck('id')->toArray();
            $notFoundIds = array_diff($ids, $foundIds);

            $reason = $request->reason
                . ' | By: ' . $user->name
                . ' | Date: ' . Carbon::now()->format('Y-m-d H:i A');

            DB::beginTransaction();

            // ✅ Save reason first
            Office::whereIn('id', $foundIds)
                ->where('status', 4)
                ->update(['office_notes' => $reason]);

            ModuleNote::whereIn('module_noteable_id', $foundIds)
                ->where('module_noteable_type', Office::class)
                ->update(['status' => 0]); // example of updating existing notes if needed

            // ✅ Insert notes (bulk)
            ModuleNote::insert(
                array_map(function ($officeId) use ($user, $reason) {
                    return [
                        'module_note_uid' => md5($officeId),
                        'module_noteable_id' => $officeId,
                        'module_noteable_type' => Office::class,
                        'details' => $reason,
                        'user_id' => $user->id, // 🔥 important
                        'created_at' => Carbon::now(),
                        'updated_at' => Carbon::now(),
                    ];
                }, $foundIds)
            );

            Contact::whereIn('contactable_id', $foundIds)
                ->where('contactable_type', Office::class)
                ->delete();

            Sale::whereIn('office_id', $foundIds)
                ->where('status', 4)
                ->delete();

            Unit::whereIn('office_id', $foundIds)
                ->where('status', 4)
                ->delete();

            // Delete the office
            Office::whereIn('id', $foundIds)->where('status', 4)->delete();

            DB::commit();

            $response = [
                'status' => true,
                'message' => count($foundIds) . ' office(s) deleted successfully',
                'deleted' => $foundIds,
            ];

            if (! empty($notFoundIds)) {
                $response['not_found'] = array_values($notFoundIds);
            }

            return response()->json($response);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => false,
                'message' => 'Something went wrong',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function scrappedUnitDestroy(Request $request)
    {
        $user = Auth::user();

        try {
            $ids = $request->has('id')
                ? (is_array($request->id) ? $request->id : [$request->id])
                : [];

            if (empty($ids)) {
                return response()->json([
                    'status' => false,
                    'message' => 'No IDs provided',
                ], 400);
            }

            $units = Unit::whereIn('id', $ids)->where('status', 4)->get();

            $reason = $request->reason
                . ' | By: ' . $user->name
                . ' Date: ' . Carbon::now()->format('Y-m-d H:i A');

            if ($units->isEmpty()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unit(s) not found',
                ], 404);
            }

            $foundIds = $units->pluck('id')->toArray();
            $notFoundIds = array_diff($ids, $foundIds);

            DB::beginTransaction();

            // ✅ Save reason first
            Unit::whereIn('id', $foundIds)
                ->where('status', 4)
                ->update(['unit_notes' => $reason]);

            ModuleNote::whereIn('module_noteable_id', $foundIds)
                ->where('module_noteable_type', Unit::class)
                ->update(['status' => 0]); // example of updating existing notes if needed

            // ✅ Insert notes (bulk)
            ModuleNote::insert(
                array_map(function ($unitId) use ($user, $reason) {
                    return [
                        'module_note_uid' => md5($unitId),
                        'module_noteable_id' => $unitId,
                        'module_noteable_type' => Unit::class,
                        'details' => $reason,
                        'user_id' => $user->id, // 🔥 important
                        'created_at' => Carbon::now(),
                        'updated_at' => Carbon::now(),
                    ];
                }, $foundIds)
            );

            Contact::whereIn('contactable_id', $foundIds)
                ->where('contactable_type', Unit::class)
                ->delete();

            Sale::whereIn('unit_id', $foundIds)->where('status', 4)->delete();

            Unit::whereIn('id', $foundIds)->where('status', 4)->delete();

            DB::commit();

            $response = [
                'status' => true,
                'message' => count($foundIds) . ' unit(s) deleted successfully',
                'deleted' => $foundIds,
            ];

            if (! empty($notFoundIds)) {
                $response['not_found'] = array_values($notFoundIds);
            }

            return response()->json($response);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => false,
                'message' => 'Something went wrong',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function scrappedSaleDestroy(Request $request)
    {
        $user = Auth::user();

        try {
            $ids = $request->has('id')
                ? (is_array($request->id) ? $request->id : [$request->id])
                : [];

            if (empty($ids)) {
                return response()->json([
                    'status' => false,
                    'message' => 'No IDs provided',
                ], 400);
            }

            $sales = Sale::whereIn('id', $ids)->where('status', 4)->get();

            $reason = $request->reason
                . ' | By: ' . $user->name
                . ' Date: ' . Carbon::now()->format('Y-m-d H:i A');

            if ($sales->isEmpty()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Sale(s) not found or not scrapped',
                ], 404);
            }

            $foundIds = $sales->pluck('id')->toArray();
            $notFoundIds = array_diff($ids, $foundIds);

            $officeIds = $sales->pluck('office_id')->filter()->unique()->toArray();
            $unitIds = $sales->pluck('unit_id')->filter()->unique()->toArray();

            DB::beginTransaction();

            // ✅ Save reason first
            Sale::whereIn('id', $foundIds)
                ->where('status', 4)
                ->update(['sale_notes' => $reason]);

            ModuleNote::whereIn('module_noteable_id', $foundIds)
                ->where('module_noteable_type', Sale::class)
                ->update(['status' => 0]); // example of updating existing notes if needed

            // ✅ Insert notes (bulk)
            ModuleNote::insert(
                array_map(function ($saleId) use ($user, $reason) {
                    return [
                        'module_note_uid' => md5($saleId),
                        'module_noteable_id' => $saleId,
                        'module_noteable_type' => Sale::class,
                        'details' => $reason,
                        'user_id' => $user->id, // 🔥 important
                        'created_at' => Carbon::now(),
                        'updated_at' => Carbon::now(),
                    ];
                }, $foundIds)
            );

            // Check which offices have ONLY the requested sales linked
            $soloOfficeIds = [];
            foreach ($officeIds as $officeId) {
                $totalSalesForOffice = Sale::where('office_id', $officeId)->count();
                $requestedSalesForOffice = $sales->where('office_id', $officeId)->count();

                // If all sales for this office are in the requested deletion list
                if ($totalSalesForOffice === $requestedSalesForOffice) {
                    $soloOfficeIds[] = $officeId; // safe to delete office
                }
            }

            // Check which units have ONLY the requested sales linked
            $soloUnitIds = [];
            foreach ($unitIds as $unitId) {
                $totalSalesForUnit = Sale::where('unit_id', $unitId)->count();
                $requestedSalesForUnit = $sales->where('unit_id', $unitId)->count();

                // If all sales for this unit are in the requested deletion list
                if ($totalSalesForUnit === $requestedSalesForUnit) {
                    $soloUnitIds[] = $unitId; // safe to delete unit
                }
            }

            // Delete sale contacts
            Contact::whereIn('contactable_id', $foundIds)
                ->where('contactable_type', Sale::class)
                ->delete();

            // Delete office contacts and offices only if no other sales reference them
            if (! empty($soloOfficeIds)) {
                Contact::whereIn('contactable_id', $soloOfficeIds)
                    ->where('contactable_type', Office::class)
                    ->delete();

                Office::whereIn('id', $soloOfficeIds)->delete();
            }

            // Delete units only if no other sales reference them
            if (! empty($soloUnitIds)) {
                Unit::whereIn('id', $soloUnitIds)->delete();
            }

            // Always delete the requested sales
            Sale::whereIn('id', $foundIds)->delete();

            DB::commit();

            $response = [
                'status' => true,
                'message' => count($foundIds) . ' sale(s) deleted successfully',
                'deleted_sales' => $foundIds,
                'deleted_offices' => $soloOfficeIds,  // offices that were also deleted
                'deleted_units' => $soloUnitIds,    // units that were also deleted
            ];

            if (! empty($notFoundIds)) {
                $response['not_found'] = array_values($notFoundIds);
            }

            return response()->json($response);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => false,
                'message' => 'Something went wrong',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // scrap restore
    public function scrappedOfficeRestore(Request $request)
    {
        $user = Auth::user();

        try {
            $ids = $request->has('id')
                ? (is_array($request->id) ? $request->id : [$request->id])
                : [];

            $offices = Office::withTrashed()
                ->whereIn('id', $ids)
                ->where('status', 4)
                ->get();

            if ($offices->isEmpty()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Office(s) not found',
                ], 404);
            }

            $foundIds = $offices->pluck('id')->toArray();
            $notFoundIds = array_diff($ids, $foundIds);

            $reason = $request->reason
                . ' | By: ' . $user->name
                . ' | Date: ' . Carbon::now()->format('Y-m-d H:i A');

            DB::beginTransaction();

            // ✅ Save reason first
            Office::whereIn('id', $foundIds)
                ->where('status', 4)
                ->update(['office_notes' => $reason]);

            ModuleNote::whereIn('module_noteable_id', $foundIds)
                ->where('module_noteable_type', Office::class)
                ->update(['status' => 0]); // example of updating existing notes if needed

            // ✅ Insert notes (bulk)
            ModuleNote::insert(
                array_map(function ($officeId) use ($user, $reason) {
                    return [
                        'module_note_uid' => md5($officeId),
                        'module_noteable_id' => $officeId,
                        'module_noteable_type' => Office::class,
                        'details' => $reason,
                        'user_id' => $user->id, // 🔥 important
                        'created_at' => Carbon::now(),
                        'updated_at' => Carbon::now(),
                    ];
                }, $foundIds)
            );

            // Restore Office
            Office::withTrashed()
                ->whereIn('id', $foundIds)
                ->where('status', 4)
                ->restore();

            // Restore related Contacts
            Contact::withTrashed()
                ->whereIn('contactable_id', $foundIds)
                ->where('contactable_type', Office::class)
                ->restore();

            // Restore related Sales
            Sale::withTrashed()
                ->whereIn('office_id', $foundIds)
                ->where('status', 4)
                ->restore();

            // Restore related Units
            Unit::withTrashed()
                ->whereIn('office_id', $foundIds)
                ->where('status', 4)
                ->restore();

            DB::commit();

            $response = [
                'status' => true,
                'message' => count($foundIds) . ' office(s) restored successfully',
                'restored' => $foundIds,
            ];

            if (! empty($notFoundIds)) {
                $response['not_found'] = array_values($notFoundIds);
            }

            return response()->json($response);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => false,
                'message' => 'Something went wrong',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function scrappedUnitRestore(Request $request)
    {
        $user = Auth::user();

        try {
            $ids = $request->has('id')
                ? (is_array($request->id) ? $request->id : [$request->id])
                : [];

            if (empty($ids)) {
                return response()->json([
                    'status' => false,
                    'message' => 'No IDs provided',
                ], 400);
            }

            $reason = $request->reason
                . ' | By: ' . $user->name
                . ' | Date: ' . Carbon::now()->format('Y-m-d H:i A');

            $units = Unit::withTrashed()
                ->whereIn('id', $ids)
                ->where('status', 4)
                ->get();

            if ($units->isEmpty()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unit(s) not found',
                ], 404);
            }

            $foundIds = $units->pluck('id')->toArray();
            $notFoundIds = array_diff($ids, $foundIds);

            DB::beginTransaction();

            // ✅ Save reason first
            Unit::whereIn('id', $foundIds)
                ->where('status', 4)
                ->update(['unit_notes' => $reason]);

            Contact::withTrashed()
                ->whereIn('contactable_id', $foundIds)
                ->where('contactable_type', Unit::class)
                ->restore();

            Sale::withTrashed()
                ->whereIn('unit_id', $foundIds)
                ->where('status', 4)
                ->restore();

            Unit::withTrashed()
                ->whereIn('id', $foundIds)
                ->where('status', 4)
                ->restore();

            ModuleNote::whereIn('module_noteable_id', $foundIds)
                ->where('module_noteable_type', Unit::class)
                ->update(['status' => 0]); // example of updating existing notes if needed

            // ✅ Insert notes (bulk)
            ModuleNote::insert(
                array_map(function ($unitId) use ($user, $reason) {
                    return [
                        'module_note_uid' => md5($unitId),
                        'module_noteable_id' => $unitId,
                        'module_noteable_type' => Unit::class,
                        'details' => $reason,
                        'user_id' => $user->id, // 🔥 important
                        'created_at' => Carbon::now(),
                        'updated_at' => Carbon::now(),
                    ];
                }, $foundIds)
            );

            DB::commit();

            $response = [
                'status' => true,
                'message' => count($foundIds) . ' unit(s) restored successfully',
                'restored' => $foundIds,
            ];

            if (! empty($notFoundIds)) {
                $response['not_found'] = array_values($notFoundIds);
            }

            return response()->json($response);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => false,
                'message' => 'Something went wrong',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function scrappedSaleRestore(Request $request)
    {
        $user = Auth::user();

        try {
            $ids = $request->has('id')
                ? (is_array($request->id) ? $request->id : [$request->id])
                : [];

            if (empty($ids)) {
                return response()->json([
                    'status' => false,
                    'message' => 'No IDs provided',
                ], 400);
            }

            $reason = $request->reason
                . ' | By: ' . $user->name
                . ' | Date: ' . Carbon::now()->format('Y-m-d H:i A');

            $sales = Sale::withTrashed()
                ->whereIn('id', $ids)
                ->where('status', 4)
                ->get();

            if ($sales->isEmpty()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Sale(s) not found or not scrapped',
                ], 404);
            }

            $foundIds = $sales->pluck('id')->toArray();
            $notFoundIds = array_diff($ids, $foundIds);

            $officeIds = $sales->pluck('office_id')->filter()->unique()->toArray();
            $unitIds = $sales->pluck('unit_id')->filter()->unique()->toArray();

            DB::beginTransaction();

            // ✅ Save reason first
            Sale::whereIn('id', $foundIds)
                ->where('status', 4)
                ->update(['sale_notes' => $reason]);

            // Check which offices have ONLY the requested sales linked
            $soloOfficeIds = [];
            foreach ($officeIds as $officeId) {
                $totalSalesForOffice = Sale::withTrashed()->where('office_id', $officeId)->count();
                $requestedSalesForOffice = $sales->where('office_id', $officeId)->count();

                // If all sales for this office are in the requested deletion list
                if ($totalSalesForOffice === $requestedSalesForOffice) {
                    $soloOfficeIds[] = $officeId; // safe to delete office
                }
            }

            // Check which units have ONLY the requested sales linked
            $soloUnitIds = [];
            foreach ($unitIds as $unitId) {
                $totalSalesForUnit = Sale::where('unit_id', $unitId)->count();
                $requestedSalesForUnit = $sales->where('unit_id', $unitId)->count();

                // If all sales for this unit are in the requested deletion list
                if ($totalSalesForUnit === $requestedSalesForUnit) {
                    $soloUnitIds[] = $unitId; // safe to delete unit
                }
            }

            // Delete sale contacts
            Contact::withTrashed()
                ->whereIn('contactable_id', $foundIds)
                ->where('contactable_type', Sale::class)
                ->restore();

            // Delete office contacts and offices only if no other sales reference them
            if (! empty($soloOfficeIds)) {
                Contact::withTrashed()
                    ->whereIn('contactable_id', $soloOfficeIds)
                    ->where('contactable_type', Office::class)
                    ->restore();

                Office::withTrashed()
                    ->whereIn('id', $soloOfficeIds)
                    ->restore();
            }

            // Delete units only if no other sales reference them
            if (! empty($soloUnitIds)) {
                Unit::withTrashed()
                    ->whereIn('id', $soloUnitIds)
                    ->restore();
            }

            ModuleNote::whereIn('module_noteable_id', $foundIds)
                ->where('module_noteable_type', Sale::class)
                ->update(['status' => 0]); // example of updating existing notes if needed

            // ✅ Insert notes (bulk)
            ModuleNote::insert(
                array_map(function ($saleId) use ($user, $reason) {
                    return [
                        'module_note_uid' => md5($saleId),
                        'module_noteable_id' => $saleId,
                        'module_noteable_type' => Sale::class,
                        'details' => $reason,
                        'user_id' => $user->id, // 🔥 important
                        'created_at' => Carbon::now(),
                        'updated_at' => Carbon::now(),
                    ];
                }, $foundIds)
            );

            // Always delete the requested sales
            Sale::withTrashed()
                ->whereIn('id', $foundIds)
                ->restore();

            DB::commit();

            $response = [
                'status' => true,
                'message' => count($foundIds) . ' sale(s) restored successfully',
                'restored_sales' => $foundIds,
                'restored_offices' => $soloOfficeIds,  // offices that were also restored
                'restored_units' => $soloUnitIds,    // units that were also restored
            ];

            if (! empty($notFoundIds)) {
                $response['not_found'] = array_values($notFoundIds);
            }

            return response()->json($response);
        } catch (Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => false,
                'message' => 'Something went wrong',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // scrap approved
    public function scrappedSaleApprove(Request $request)
    {
        try {
            if ($request->has('id')) {
                $raw = $request->id;

                if (is_array($raw)) {
                    $ids = $raw;
                } else {
                    $decoded = json_decode($raw, true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                        $ids = $decoded;
                    } else {
                        $ids = array_map('trim', explode(',', $raw));
                    }
                }
            } else {
                $ids = []; // Bug 1 fix: ← was [$request->id] which would always be null here
            }

            $ids = array_map('intval', array_filter($ids));

            if (empty($ids)) {
                return response()->json([
                    'status' => false,
                    'message' => 'No sale IDs provided',
                ], 422);
            }

            $sales = Sale::whereIn('id', $ids)->where('status', 4)->get();

            if ($sales->isEmpty()) {
                return response()->json([
                    'status' => false,
                    'message' => 'No scrapped sales found',
                ], 404);
            }

            $foundIds = $sales->pluck('id')->toArray();
            $notFoundIds = array_diff($ids, $foundIds);

            $officeIds = $sales->pluck('office_id')->filter()->unique()->toArray();
            $unitIds = $sales->pluck('unit_id')->filter()->unique()->toArray();

            // Bug 2 fix: collections are always truthy even when empty
            // no need for if($offices) — foreach handles empty collections
            Office::whereIn('id', $officeIds)
                ->where('status', 4)        // Bug 3 fix: ← filter in query not in loop
                ->update([
                    'status' => 1,
                    'office_notes' => 'Sale has been approved.',
                ]);

            Unit::whereIn('id', $unitIds)
                ->where('status', 4)
                ->update([
                    'status' => 1,
                    'unit_notes' => 'Sale has been approved.',
                ]);

            Sale::whereIn('id', $foundIds)
                ->where('status', 4)
                ->update([
                    'status' => 1,
                    'sale_notes' => 'Sale has been approved.',
                ]);

            $response = [
                'status' => true,
                'message' => count($foundIds) . ' sale(s) approved successfully',
                'approved' => $foundIds,
            ];

            if (! empty($notFoundIds)) {
                $response['not_found'] = array_values($notFoundIds);
            }

            return response()->json($response);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function scrappedUnitApprove(Request $request)
    {
        try {
            // Handle JSON string, array, or single id
            if ($request->has('id')) {
                $raw = $request->id;

                if (is_array($raw)) {
                    $ids = $raw;
                } else {
                    // Try to decode JSON array
                    $decoded = json_decode($raw, true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                        $ids = $decoded;
                    } else {
                        // If not JSON, check for comma-separated string
                        $ids = array_map('trim', explode(',', $raw));
                    }
                }
            } else {
                $ids = [$request->id];
            }

            // Ensure all IDs are integers
            $ids = array_map('intval', array_filter($ids));

            if (empty($ids)) {
                return response()->json([
                    'status' => false,
                    'message' => 'No unit IDs provided',
                ], 422);
            }

            $units = Unit::whereIn('id', $ids)->where('status', 4)->get();
            if ($units->isEmpty()) {
                return response()->json([
                    'status' => false,
                    'message' => 'No scrapped units found',
                ], 404);
            }

            $foundIds = $units->pluck('id')->toArray();
            $notFoundIds = array_diff($ids, $foundIds);

            $officeIds = $units->pluck('office_id')->filter()->unique()->toArray();

            Office::whereIn('id', $officeIds)->where('status', 4)->update(['status' => 1, 'office_notes' => 'Unit has been approved.']);
            Unit::whereIn('id', $foundIds)->where('status', 4)->update(['status' => 1, 'unit_notes' => 'Unit has been approved.']);

            $response = [
                'status' => true,
                'message' => count($foundIds) . ' unit(s) approved successfully',
                'approved' => $foundIds,
            ];

            if (! empty($notFoundIds)) {
                $response['not_found'] = array_values($notFoundIds);
            }

            return response()->json($response);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function scrappedOfficeApprove(Request $request)
    {
        try {
            // Handle JSON string, array, or single id
            if ($request->has('id')) {
                $raw = $request->id;

                if (is_array($raw)) {
                    $ids = $raw;
                } else {
                    // Try to decode JSON array
                    $decoded = json_decode($raw, true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                        $ids = $decoded;
                    } else {
                        // If not JSON, check for comma-separated string
                        $ids = array_map('trim', explode(',', $raw));
                    }
                }
            } else {
                $ids = [$request->id];
            }

            // Ensure all IDs are integers
            $ids = array_map('intval', array_filter($ids));

            if (empty($ids)) {
                return response()->json([
                    'status' => false,
                    'message' => 'No office IDs provided',
                ], 422);
            }

            $offices = Office::whereIn('id', $ids)->where('status', 4)->get();
            if ($offices->isEmpty()) {
                return response()->json([
                    'status' => false,
                    'message' => 'No scrapped offices found',
                ], 404);
            }

            $foundIds = $offices->pluck('id')->toArray();
            $notFoundIds = array_diff($ids, $foundIds);

            Office::whereIn('id', $foundIds)->where('status', 4)->update(['status' => 1, 'office_notes' => 'Office has been approved.']);

            $response = [
                'status' => true,
                'message' => count($foundIds) . ' office(s) approved successfully',
                'approved' => $foundIds,
            ];

            if (! empty($notFoundIds)) {
                $response['not_found'] = array_values($notFoundIds);
            }

            return response()->json($response);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function getSaleEmails(Request $request)
    {
        $sale = Sale::with(['office', 'unit'])->findOrFail($request->sale_id);

        $emails = Contact::where('contactable_id', $sale->office_id)
            ->where('contactable_type', Office::class)
            ->whereNotNull('contact_email')
            ->where('contact_email', '!=', '')
            ->pluck('contact_email')
            ->map(fn($email) => trim(strtolower($email)))
            ->unique()
            ->values();

        $formattedMessage = '';
        $formattedSubject = '';
        $email_from = '';

        $emailNotification = Setting::where('key', 'email_notifications')->first();
        if ($emailNotification) {
            $email_template = EmailTemplate::where('slug', 'scrapped_offices_email')
                ->where('is_active', 1)
                ->first();

            if ($email_template && ! empty($email_template->template)) {
                $email_from = $email_template->from_email;

                $replace = [
                    $sale->office->office_name ?? '',
                    $sale->unit->unit_name ?? '',
                    $sale->sale_postcode ?? '',
                    '',
                ];
                $prev_val = ['(office_name)', '(unit_name)', '(postcode)', '(recipient_name)'];

                $formattedMessage = str_replace($prev_val, $replace, $email_template->template);
                $formattedSubject = str_replace($prev_val, $replace, $email_template->subject);
            }

            return response()->json([
                'emails' => $emails,
                'office_id' => $sale->office_id,
                'email_template' => $formattedMessage,
                'email_subject' => $formattedSubject,
                'from_email' => $email_from,
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Email notifications are disabled.',
            ]);
        }
    }

    public function getBulkEmailTemplate(Request $request)
    {
        $ids = is_array($request->ids) ? $request->ids : [$request->ids]; // match AJAX 'ids'

        // Get unique office IDs for the given sales
        $officeIds = Sale::whereIn('id', $ids)
            ->pluck('office_id')
            ->unique()
            ->toArray();

        // Get unique emails from those offices
        $emails = Contact::whereIn('contactable_id', $officeIds)
            ->where('contactable_type', Office::class)
            ->whereNotNull('contact_email')
            ->where('contact_email', '!=', '')
            ->pluck('contact_email', 'contactable_id') // key = office_id
            ->map(fn($email) => trim(strtolower($email)))
            ->unique()
            ->toArray();

        // Only keep sale IDs whose office has at least one email
        $saleIdsWithEmails = Sale::whereIn('id', $ids)
            ->whereIn('office_id', array_keys($emails))
            ->pluck('id')
            ->toArray();

        // Default template values
        $formattedMessage = '';
        $formattedSubject = '';
        $email_from = '';

        $email_template = EmailTemplate::where('slug', 'scrap_bulk_emails')
            ->where('is_active', 1)
            ->first();

        if ($email_template) {
            $email_from = $email_template->from_email;
            $formattedMessage = $email_template->template ?? '';
            $formattedSubject = $email_template->subject ?? '';
        }

        return response()->json([     // emails to send
            'email_template' => $formattedMessage,
            'subject' => $formattedSubject,
            'from_email' => $email_from,
            'sale_ids' => $saleIdsWithEmails,         // only sales with emails
        ]);
    }

    public function getBulkOfficesEmailTemplate(Request $request)
    {
        $ids = is_array($request->ids) ? $request->ids : [$request->ids]; // match AJAX 'ids'

        // Get unique emails from those offices
        $emails = Contact::whereIn('contactable_id', $ids)
            ->where('contactable_type', Office::class)
            ->whereNotNull('contact_email')
            ->where('contact_email', '!=', '')
            ->pluck('contact_email', 'contactable_id') // key = office_id
            ->map(fn($email) => trim(strtolower($email)))
            ->unique()
            ->toArray();

        // Only keep sale IDs whose office has at least one email
        $saleIdsWithEmails = Sale::whereIn('office_id', array_keys($emails))
            ->pluck('id')
            ->toArray();

        // Default template values
        $formattedMessage = '';
        $formattedSubject = '';
        $email_from = '';

        $email_template = EmailTemplate::where('slug', 'scrap_bulk_emails')
            ->where('is_active', 1)
            ->first();

        if ($email_template) {
            $email_from = $email_template->from_email;
            $formattedMessage = $email_template->template ?? '';
            $formattedSubject = $email_template->subject ?? '';
        }

        return response()->json([     // emails to send
            'email_template' => $formattedMessage,
            'subject' => $formattedSubject,
            'from_email' => $email_from,
            'sale_ids' => $saleIdsWithEmails,         // only sales with emails
        ]);
    }

    public function sendEmailToOffices(Request $request)
    {
        $request->validate([
            'to_email' => 'required',
            'from_email' => 'required',
            'subject' => 'required|string',
            'message' => 'required|string',
        ]);

        // Parse and clean emails
        $emails = array_filter(array_map('trim', explode(',', $request->to_email)));
        if (empty($emails)) {
            return response()->json([
                'success' => false,
                'message' => 'No valid emails provided.',
            ], 422);
        }

        $email_from = $request->from_email;
        $email_subject = $request->subject;
        $formattedMessage = $request->message;
        $email_title = $request->email_title;

        // Normalize sale_id to an array (works for single or multiple)
        $sale_ids = is_array($request->sale_id) ? $request->sale_id : [$request->sale_id];

        $success = [];
        $failed = [];

        foreach ($emails as $email) {
            $email = strtolower(trim($email));

            // Validate email format
            if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $failed[] = $email . ' (invalid format)';

                continue;
            }

            // Loop through each sale ID
            foreach ($sale_ids as $sale_id) {
                try {
                    $is_save = $this->saveEmailDB(
                        $email,
                        $email_from,
                        $email_subject,
                        $formattedMessage,
                        $email_title,
                        null,
                        $sale_id
                    );

                    if (! $is_save) {
                        Log::warning("Email save failed: $email | Sale ID: $sale_id");
                        $failed[] = "$email (DB save failed for Sale ID: $sale_id)";
                    } else {
                        $success[] = "$email (Sale ID: $sale_id)";
                    }
                } catch (\Exception $e) {
                    Log::error("Email send failed: $email | Sale ID: $sale_id | Error: " . $e->getMessage());
                    $failed[] = "$email (error for Sale ID: $sale_id)";
                }
            }
        }

        // Build response
        if (empty($failed)) {
            return response()->json([
                'success' => true,
                'message' => 'Email sent successfully to ' . count($success) . ' recipient(s).',
                'sent_to' => $success,
            ]);
        } elseif (! empty($success)) {
            return response()->json([
                'success' => true,
                'message' => 'Email sent to some recipients.',
                'sent_to' => $success,
                'failed' => $failed,
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send email to all recipients.',
                'failed' => $failed,
            ], 500);
        }
    }

    public function sendBulkEmailsToOffices(Request $request)
    {
        $request->validate([
            'from_email' => 'required|email',
            'subject' => 'required|string|max:255',
            'message' => 'required|string',
            'email_title' => 'required|string',
            'sale_ids' => 'required|array',       // ← validate as array
            'sale_ids.*' => 'required|integer',     // ← each value must be integer
        ]);

        $email_from = $request->from_email;
        $email_subject = $request->subject;
        $formattedMessage = $request->message;
        $email_title = $request->email_title;
        $sale_ids = $request->sale_ids;

        $success = [];
        $failed = [];

        foreach ($sale_ids as $sale_id) {

            // Guard: sale not found
            $sale = Sale::find($sale_id);
            if (! $sale) {
                $failed[] = "Sale ID: {$sale_id} (not found)";

                continue;
            }

            // Guard: office not found
            $office = Office::with('contact')->find($sale->office_id);
            if (! $office) {
                $failed[] = "Sale ID: {$sale_id} (office not found)";

                continue;
            }

            // Fix: contacts (plural) not contact
            $emails = $office->contact
                ->whereNotNull('contact_email')
                ->where('contact_email', '!=', '')
                ->pluck('contact_email')
                ->map(fn($email) => trim(strtolower($email)))
                ->unique()
                ->values();

            // Guard: no emails found for this office
            if ($emails->isEmpty()) {
                $failed[] = "Sale ID: {$sale_id} (no emails found for office)";

                continue;
            }

            foreach ($emails as $email) {

                // Validate each email format
                if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $failed[] = "{$email} (invalid format for Sale ID: {$sale_id})";

                    continue;
                }

                try {
                    $is_save = $this->saveEmailDB(
                        $email,
                        $email_from,
                        $email_subject,
                        $formattedMessage,
                        $email_title,
                        null,
                        $sale_id
                    );

                    if (! $is_save) {
                        Log::warning("Email save failed: {$email} | Sale ID: {$sale_id}");
                        $failed[] = "{$email} (DB save failed for Sale ID: {$sale_id})";
                    } else {
                        $success[] = "{$email} (Sale ID: {$sale_id})";
                    }
                } catch (\Exception $e) {
                    Log::error("Email send failed: {$email} | Sale ID: {$sale_id} | Error: " . $e->getMessage());
                    $failed[] = "{$email} (error for Sale ID: {$sale_id})";
                }
            }
        }

        if (empty($failed)) {
            return response()->json([
                'success' => true,
                'message' => 'Email sent successfully to ' . count($success) . ' recipient(s).',
                'sent_to' => $success,
            ]);
        } elseif (! empty($success)) {
            return response()->json([
                'success' => true,
                'message' => 'Email sent to ' . count($success) . ' of ' . (count($success) + count($failed)) . ' recipient(s).',
                'sent_to' => $success,
                'failed' => $failed,
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send email to all recipients.',
                'failed' => $failed,
            ], 500);
        }
    }
}
