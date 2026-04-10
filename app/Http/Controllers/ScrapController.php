<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Horsefly\Setting;
use Horsefly\Office;
use Horsefly\Unit;
use Horsefly\JobTitle;
use Horsefly\JobCategory;
use Horsefly\JobSource;
use Horsefly\EmailTemplate;
use Horsefly\Sale;
use Horsefly\Contact;
use Horsefly\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Services\ScrapService;
use Yajra\DataTables\Facades\DataTables;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Maatwebsite\Excel\Facades\Excel;
use App\Traits\Geocode;
use Illuminate\Support\Str;
use League\Csv\Reader;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Gate;
use App\Traits\SendEmails;


class ScrapController extends Controller
{
    use SendEmails, Geocode;

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

        $input = $request->input('input', []);
        if (is_string($input)) {
            $input = json_decode($input, true) ?: [];
        }

        try {
            // ---------------------------------------------------------------
            // 1. FETCH JOBS from the scraper API via ScrapService
            // ---------------------------------------------------------------
            $service = new ScrapService();
            $jobs = $service->runByKey($actorKey, $input);

            if (empty($jobs) || !is_array($jobs)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No jobs returned from scraper API.',
                ], 400);
            }

            // ---------------------------------------------------------------
            // 2. IMPORT JOBS into DB
            // ---------------------------------------------------------------
            $user = Auth::user() ?? User::first();

            if (!$user) {
                return response()->json(['success' => false, 'message' => 'No user found'], 400);
            }


            // $importedCount = $this->persistJobs($jobs, $user);
            // Route to the correct persist method based on actorKey
            $importedCount = match (true) {
                str_contains($actorKey, 'scrap_apify_indeed') => $this->persistJobsIndeed($jobs, $user),
                str_contains($actorKey, 'scrap_apify_totaljobs') => $this->persistJobsTotalJob($jobs, $user),
                default => throw new \InvalidArgumentException("No persist handler found for actor_key: [{$actorKey}]"),
            };

            return response()->json([
                'success' => true,
                'message' => "Imported {$importedCount} jobs from [{$actorKey}]",
            ]);
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

    /**
     * Persist an array of raw scraper job rows into the DB.
     * Returns the number of newly created Sale records.
     */
    public function persistJobsIndeed(array $jobs, $user): int
    {
        $importedCount = 0;

        foreach ($jobs as $job) {

            DB::beginTransaction();

            try {
                // ===============================
                // COMPANY / OFFICE
                // ===============================
                $companyName = $job['companyName'] ?? 'Unknown Company';
                $companyUrl = $job['companyLinks']['corporateWebsite'] ?? null;
                $companyDesc = $job['descriptionText'] ?? null;

                $postcode = $job['location']['postalCode'] ?? 'UNKNOWN';
                $lat = $job['location']['latitude'] ?? null;
                $lng = $job['location']['longitude'] ?? null;
                $city = $job['location']['city'] ?? null;

                $office = Office::whereRaw('LOWER(office_name)=?', [strtolower(trim($companyName))])
                    ->whereRaw("REPLACE(office_postcode,' ','')=?", [str_replace(' ', '', $postcode)])
                    ->first();

                if (!$office) {
                    $office = Office::create([
                        'office_name' => $companyName,
                        'office_postcode' => $postcode,
                        'user_id' => $user->id,
                        'office_type' => 'head_office',
                        'office_website' => $companyUrl,
                        'office_notes' => substr($companyDesc, 0, 500),
                        'office_lat' => $lat,
                        'office_lng' => $lng,
                        'status' => 4, //4=Scrapped
                    ]);

                    $office->update(['office_uid' => md5($office->id)]);
                }

                // ===============================
                // COLLECT ALL CONTACTS
                // ===============================
                $contactsMap = []; // keyed by email to auto-deduplicate

                // Source 1: job['contacts'] array
                if (!empty($job['contacts']) && is_array($job['contacts'])) {
                    foreach ($job['contacts'] as $c) {
                        $email = isset($c['contactEmail']) ? strtolower(trim($c['contactEmail'])) : null;
                        $name = isset($c['contactName']) ? trim($c['contactName']) : null;
                        $phone = isset($c['contactPhone']) ? trim($c['contactPhone']) : null;

                        if ($email && filter_var($email, FILTER_VALIDATE_EMAIL)) {

                            // Skip if this email already exists for any Office contact
                            $alreadyExists = Contact::where('contact_email', $email)
                                ->where('contactable_type', Office::class)
                                ->exists();

                            if ($alreadyExists) {
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

                // Source 2: parse from description text
                $descriptionText = $job['descriptionText'] ?? '';

                // ===============================
                // EXTRACT EMAILS FROM DESCRIPTION
                // ===============================
                if (!empty($descriptionText)) {

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

                            if (filter_var($email, FILTER_VALIDATE_EMAIL) && !isset($contactsMap[$email])) {

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

                            if (filter_var($email, FILTER_VALIDATE_EMAIL) && !isset($contactsMap[$email])) {

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

                            if (filter_var($email, FILTER_VALIDATE_EMAIL) && !isset($contactsMap[$email])) {

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
                $validCity = $city && !in_array(strtolower($city), ['unknown city', 'null']);
                $unitName = $validCity ? $city : $companyName . ' Main Unit';

                $unit = Unit::where('office_id', $office->id)
                    ->whereRaw('LOWER(unit_name)=?', [strtolower(trim($unitName))])
                    ->whereRaw("REPLACE(unit_postcode,' ','')=?", [str_replace(' ', '', $postcode)])
                    ->first();

                if (!$unit) {
                    $unit = Unit::create([
                        'office_id' => $office->id,
                        'unit_name' => $unitName,
                        'unit_postcode' => $postcode,
                        'user_id' => $user->id,
                        'unit_website' => $companyUrl,
                        'unit_notes' => 'Scrapped from Indeed',
                        'lat' => $lat,
                        'lng' => $lng,
                        'status' => 4, // 4=Scrapped
                    ]);

                    $unit->update(['unit_uid' => md5($unit->id)]);
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
                $timing = count($jobTypes) ? implode(', ', str_replace('-', ' ', $jobTypes)) : 'Not specified';
                $vacancies = $job['numOfCandidates'] ?? 2; // set default 2 as per requirement
                $benefits = isset($job['benefits']) ? implode(', ', $job['benefits']) : 'None';

                // ===============================
                // LAT/LNG FALLBACK
                // ===============================
                if (!$lat || !$lng) {
                    $postcodeRow = DB::table('postcodes')
                        ->whereRaw("LOWER(REPLACE(postcode,' ',''))=?", [strtolower(str_replace(' ', '', $postcode))])
                        ->first();

                    if ($postcodeRow) {
                        $lat = $postcodeRow->lat;
                        $lng = $postcodeRow->lng;
                    }
                }

                // ===============================
                // DUPLICATE CHECK
                // ===============================
                $jobUrl = $job['jobUrl'] ?? null;

                $existingSale = Sale::where('office_id', $office->id)
                    ->where('unit_id', $unit->id)
                    ->whereRaw("REPLACE(sale_postcode,' ','')=?", [str_replace(' ', '', $postcode)])
                    ->first();

                if (!$existingSale) {

                    $sale = Sale::create([
                        'user_id' => $user->id,
                        'office_id' => $office->id,
                        'unit_id' => $unit->id,
                        'job_category_id' => $jobCategory,
                        'job_title_id' => $jobTitleId,
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
                        'status' => 4, //4=Scrapped
                    ]);

                    $sale->update(['sale_uid' => md5($sale->id)]);

                    $importedCount++;
                }

                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('[ScrapImport] persistJobs row error: ' . $e->getMessage());
            }
        }

        return $importedCount;
    }

    public function persistJobsTotalJob(array $jobs, $user): int
    {
        $importedCount = 0;

        foreach ($jobs as $job) {

            DB::beginTransaction();

            try {
                // ===============================
                // COMPANY / OFFICE
                // ===============================

                // Clean company name — strip trailing "\nView Profile" or similar
                $companyName = trim(explode("\n", $job['companyName'] ?? 'Unknown Company')[0]);
                $companyUrl = $job['companyURL'] ?? null;
                $companyDesc = $job['descriptionText'] ?? 'Scrapped from TotalJobs';

                // ===============================
                // PARSE LOCATION STRING
                // e.g. "Dumfries (DG2), DG2 9JW"
                // ===============================
                $locationRaw = $job['location'] ?? '';
                $postcode = 'UNKNOWN';
                $city = null;
                $lat = null;
                $lng = null;

                if (!empty($locationRaw)) {
                    // Extract UK postcode from end of string (e.g. "DG2 9JW")
                    if (preg_match('/([A-Z]{1,2}\d{1,2}[A-Z]?\s*\d[A-Z]{2})$/i', $locationRaw, $pcMatch)) {
                        $postcode = strtoupper(trim($pcMatch[1]));

                        // 1. Try to find a match in the full postcodes table first
                        $postcode_query = DB::table('postcodes')
                            ->whereRaw("LOWER(REPLACE(postcode, ' ', '')) = ?", [$postcode])
                            ->first();

                        // 2. Fallback: If not found in full postcodes, check outcodes
                        if (!$postcode_query) {
                            $postcode_query = DB::table('outcodepostcodes')
                                ->whereRaw("LOWER(REPLACE(outcode, ' ', '')) = ?", [$postcode])
                                ->first();
                        }

                        if (!$postcode_query) {
                            try {
                                $result = $this->geocode($postcode);

                                // If geocode fails, throw
                                if (!isset($result['lat']) || !isset($result['lng'])) {
                                    throw new \Exception('Geolocation failed. Latitude and longitude not found.');
                                }

                                $lat = $result['lat'];
                                $lng = $result['lng'];
                            } catch (\Exception $e) {
                                Log::error('[ScrapImport] Geocode failed for postcode ' . $postcode . ': ' . $e->getMessage());
                            }
                        } else {
                            $lat = $postcode_query->lat;
                            $lng = $postcode_query->lng;
                        }
                    }

                    // Extract city — everything before first "(" or ","
                    if (preg_match('/^([^,(]+)/', $locationRaw, $cityMatch)) {
                        $city = trim($cityMatch[1]);
                    }
                }

                $office = Office::whereRaw('LOWER(office_name)=?', [strtolower(trim($companyName))])
                    ->whereRaw("REPLACE(office_postcode,' ','')=?", [str_replace(' ', '', $postcode)])
                    ->first();

                if (!$office) {
                    $office = Office::create([
                        'office_name' => $companyName,
                        'office_postcode' => $postcode,
                        'user_id' => $user->id,
                        'office_type' => 'head_office',
                        'office_website' => $companyUrl,
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
                $descriptionText = $job['descriptionText'] ?? '';

                // Source 1: extract emails from description
                if (!empty($descriptionText)) {

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

                            if (filter_var($email, FILTER_VALIDATE_EMAIL) && !isset($contactsMap[$email])) {

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

                            if (filter_var($email, FILTER_VALIDATE_EMAIL) && !isset($contactsMap[$email])) {

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

                            if (filter_var($email, FILTER_VALIDATE_EMAIL) && !isset($contactsMap[$email])) {

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
                $validCity = $city && !in_array(strtolower($city), ['unknown city', 'null']);
                $unitName = $validCity ? $city : $companyName . ' Main Unit';

                $unit = Unit::where('office_id', $office->id)
                    ->whereRaw('LOWER(unit_name)=?', [strtolower(trim($unitName))])
                    ->whereRaw("REPLACE(unit_postcode,' ','')=?", [str_replace(' ', '', $postcode)])
                    ->first();

                if (!$unit) {
                    $unit = Unit::create([
                        'office_id' => $office->id,
                        'unit_name' => $unitName,
                        'unit_postcode' => $postcode,
                        'user_id' => $user->id,
                        'unit_website' => $companyUrl,
                        'unit_notes' => 'Scrapped from TotalJobs',
                        'lat' => $lat,
                        'lng' => $lng,
                        'status' => 4,
                    ]);

                    $unit->update(['unit_uid' => md5($unit->id)]);
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
                $timing = !empty($jobTypeRaw)
                    ? str_replace('-', ' ', $jobTypeRaw)   // e.g. "Permanent"
                    : 'Not specified';

                $vacancies = $job['numOfCandidates'] ?? 2;
                $benefits = isset($job['benefits']) && is_array($job['benefits'])
                    ? implode(', ', $job['benefits'])
                    : 'None';

                // ===============================
                // LAT/LNG FALLBACK via postcodes table
                // ===============================
                if (!$lat || !$lng) {
                    $postcodeRow = DB::table('postcodes')
                        ->whereRaw("LOWER(REPLACE(postcode,' ',''))=?", [strtolower(str_replace(' ', '', $postcode))])
                        ->first();

                    if ($postcodeRow) {
                        $lat = $postcodeRow->lat;
                        $lng = $postcodeRow->lng;
                    }
                }

                // ===============================
                // DUPLICATE CHECK
                // TotalJobs has no jobUrl — use numeric id instead
                // ===============================
                $jobId = $job['id'] ?? null;
                $jobRef = 'Scrap TotalJobs Job - ' . $jobId;

                $existingSale = Sale::where('office_id', $office->id)
                    ->where('unit_id', $unit->id)
                    ->whereRaw("REPLACE(sale_postcode,' ','')=?", [str_replace(' ', '', $postcode)])
                    ->first();

                if (!$existingSale) {
                    $sale = Sale::create([
                        'user_id' => $user->id,
                        'office_id' => $office->id,
                        'unit_id' => $unit->id,
                        'job_category_id' => $jobCategory,
                        'job_title_id' => $jobTitleId,
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
                    $importedCount++;
                }

                DB::commit();

            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('[ScrapImport] persistJobsTotalJob row error: ' . $e->getMessage(), [
                    'job_id' => $job['id'] ?? null,
                ]);
            }
        }

        return $importedCount;
    }

    public function officeIndex()
    {
        return view('scrapped.offices_list');
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
        $offices = Office::where('status', 4)->orderBy('office_name', 'asc')->get();
        $users = User::where('is_active', 1)->orderBy('name', 'asc')->get();

        return view('scrapped.sales_list', compact('jobCategories', 'jobTitles', 'offices', 'users'));
    }
    public function getScrappedOffices(Request $request)
    {
        $statusFilter = $request->input('status_filter', '');

        // Base query
        $model = Office::query()
            ->with(['contact']) // Eager load contact relationship to solve N+1 Problem
            ->leftJoin('contacts', function ($join) {
                $join->on('contacts.contactable_id', '=', 'offices.id')
                    ->where('contacts.contactable_type', 'Horsefly\\Office');
            })
            ->select('offices.*')
            ->where('offices.status', 4)
            ->distinct();

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
                if (!empty($allMatchingIds)) {
                    $model->whereIn('offices.id', $allMatchingIds);
                }
            }
        }

        // Sorting logic
        $sortableColumns = [
            'office_name',
            'office_postcode',
            'office_type',
            'status',
            'created_at',
            'updated_at',
        ];

        if ($request->has('order')) {
            $orderColumnIndex = $request->input('order.0.column');
            $orderColumn = $request->input("columns.$orderColumnIndex.data");
            $orderDirection = $request->input('order.0.dir', 'asc');

            if ($orderColumn && in_array($orderColumn, $sortableColumns)) { // ← only sort if column is whitelisted
                $model->orderBy('offices.' . $orderColumn, $orderDirection);
            } else {
                $model->orderBy('offices.created_at', 'desc');
            }
        } else {
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
                    if (empty($rawPostcode))
                        return '<div class="text-center w-100">-</div>';

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
                    $query->whereRaw("LOWER(contacts.contact_email) LIKE ?", ["%" . strtolower(trim($keyword)) . "%"]);
                })
                ->filterColumn('contact_phone', function ($query, $keyword) {
                    $query->whereRaw("contacts.contact_phone LIKE ?", ["%" . trim($keyword) . "%"]);
                })
                ->filterColumn('contact_landline', function ($query, $keyword) {
                    $query->whereRaw("contacts.contact_landline LIKE ?", ["%" . trim($keyword) . "%"]);
                })
                ->filterColumn('office_notes', function ($query, $keyword) {
                    $query->whereRaw("LOWER(offices.office_notes) LIKE ?", ["%" . strtolower(trim($keyword)) . "%"]);
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

                    $html .= '<li><a class="dropdown-item" href="javascript:void(0);" onclick="deleteOffice(' . $office->id . ')">Delete</a></li>';


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

        $query = Unit::query()
            ->select('units.*', 'offices.office_name as office_name')
            ->leftJoin('offices', 'units.office_id', '=', 'offices.id')
            ->whereNull('units.deleted_at')
            ->with('office', 'contacts')
            ->where('units.status', 4); //scrapped

        // Office filter
        if ($officeFilter !== '') {
            $query->whereIn('units.office_id', $officeFilter);
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

            if ($orderColumn && $orderColumn !== 'DT_RowIndex' && !in_array($orderColumn, $nonSortableColumns)) {
                // Map the column if needed, or directly use the column name
                // Example: if you want to map 'office_name' to 'offices.name', do it here
                $columnMap = [
                    'office_name' => 'offices.name',
                    'unit_name' => 'units.unit_name',
                    // add other mappings as needed
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
            ->filterColumn('office_name', function ($query, $keyword) {
                $words = preg_split('/\s+/', $keyword, -1, PREG_SPLIT_NO_EMPTY);
                foreach ($words as $word) {
                    $query->where('offices.office_name', 'LIKE', "%{$word}%");
                }
            })
            ->addColumn('unit_name', fn($u) => $u->formatted_unit_name)
            ->addColumn('unit_postcode', fn($u) => $u->formatted_postcode)
            ->editColumn('unit_postcode', function ($u) {
                $rawPostcode = trim($u->formatted_postcode);
                if (empty($rawPostcode))
                    return '<div class="text-center w-100">-</div>';

                $postcode = $u->formatted_postcode;
                $copyBtn = '<button type="button" class="btn btn-sm btn-link text-muted p-0 ms-2 copy-postcode" 
                                data-postcode="' . e($u->formatted_postcode) . '" title="Copy Postcode">
                                <iconify-icon icon="solar:copy-linear" class="fs-18"></iconify-icon>
                            </button>';

                return '<div class="d-flex align-items-center justify-content-between">' . $postcode . $copyBtn . '</div>';
            })
            ->addColumn(
                'contact_email',
                fn($u) =>
                $u->contacts->pluck('contact_email')->filter()->implode('<br>') ?: '-'
            )
            ->addColumn(
                'contact_phone',
                fn($u) =>
                $u->contacts->pluck('contact_phone')->filter()->implode('<br>') ?: '-'
            )
            ->addColumn(
                'contact_landline',
                fn($u) =>
                $u->contacts->pluck('contact_landline')->filter()->implode('<br>') ?: '-'
            )
            ->filterColumn('contact_email', function ($query, $keyword) {
                $keyword = trim($keyword);
                $query->whereExists(function ($q) use ($keyword) {
                    $q->select(DB::raw(1))
                        ->from('contacts')
                        ->whereColumn('contacts.contactable_id', 'units.id')
                        ->where('contacts.contactable_type', \Horsefly\Unit::class)
                        ->where('contact_email', 'LIKE', "{$keyword}%");
                });
            })
            ->filterColumn('contact_phone', function ($query, $keyword) {
                $clean = preg_replace('/[^0-9]/', '', $keyword);
                $query->whereExists(function ($q) use ($clean) {
                    $q->select(DB::raw(1))
                        ->from('contacts')
                        ->whereColumn('contacts.contactable_id', 'units.id')
                        ->where('contacts.contactable_type', \Horsefly\Unit::class)
                        ->where('contact_phone', 'LIKE', "{$clean}%");
                });
            })
            ->filterColumn('contact_landline', function ($query, $keyword) {
                $clean = preg_replace('/[^0-9]/', '', $keyword);
                $query->whereExists(function ($q) use ($clean) {
                    $q->select(DB::raw(1))
                        ->from('contacts')
                        ->whereColumn('contacts.contactable_id', 'units.id')
                        ->where('contacts.contactable_type', \Horsefly\Unit::class)
                        ->where('contact_landline', 'LIKE', "{$clean}%");
                });
            })

            ->addColumn('created_at', fn($u) => $u->formatted_created_at)
            ->addColumn('updated_at', fn($u) => $u->formatted_updated_at)
            ->addColumn(
                'unit_notes',
                fn($u) =>
                '<a href="javascript:void(0);" onclick="addShortNotesModal(' . (int) $u->id . ')">'
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
                $html .= '<li><a class="dropdown-item" href="javascript:void(0);" onclick="deleteUnit(' . $u->id . ')">Delete</a></li>';

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
                'unit_postcode'
            ])
            ->make(true);
    }
    private function formatWithUrlCTA($fullHtml, $idPrefix, $saleId, $modalTitle)
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
                    <a href="#" data-bs-toggle="modal" data-bs-target="#' . $id . '">' . $shortText . '</a>' . $urlCTA . '
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

        $model = Sale::query()
            ->select([
                // Core identifiers
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
                // Rich HTML fields (needed for modals)
                'sales.experience',
                'sales.salary',
                'sales.qualification',
                'sales.benefits',
                // Joined aliases
                'job_titles.name as job_title_name',
                'job_categories.name as job_category_name',
                'offices.office_name as office_name',
                'units.unit_name as unit_name',
                'users.name as user_name',
                // Latest note (joined subquery)
                'updated_notes.sale_note as latest_note',
                // offices contacts
                'office_contacts.office_emails as office_emails',   // "email1@x.com, email2@x.com"
                'office_contacts.office_phones as office_phones',   // "07700, 07800"
            ])
            ->leftJoin('job_titles', 'sales.job_title_id', '=', 'job_titles.id')
            ->leftJoin('job_categories', 'sales.job_category_id', '=', 'job_categories.id')
            ->leftJoin('offices', 'sales.office_id', '=', 'offices.id')
            ->leftJoin('units', 'sales.unit_id', '=', 'units.id')
            ->leftJoin('users', 'sales.user_id', '=', 'users.id')
            // Latest sale note via indexed join
            ->leftJoin(DB::raw('(SELECT sale_id, MAX(id) AS latest_id FROM sale_notes GROUP BY sale_id) AS latest_notes'), 'sales.id', '=', 'latest_notes.sale_id')
            ->leftJoin('sale_notes AS updated_notes', 'updated_notes.id', '=', 'latest_notes.latest_id')
            ->where('sales.status', 4) // scrapped
            // Latest open-audit per sale — avoids raw string escaping of backslash namespace
            ->leftJoinSub(
                DB::table('audits')
                    ->selectRaw('MAX(id) as id, auditable_id')
                    ->where('auditable_type', 'Horsefly\\Sale')
                    ->whereIn('message', ['scrapped'])
                    ->groupBy('auditable_id'),
                'latest_open_audit_ids',
                'latest_open_audit_ids.auditable_id',
                '=',
                'sales.id'
            )
            ->leftJoinSub(
                DB::table('contacts')
                    ->select([
                        'contactable_id',
                        DB::raw('GROUP_CONCAT(DISTINCT contact_email SEPARATOR ", ") as office_emails'),
                        DB::raw('GROUP_CONCAT(DISTINCT contact_phone SEPARATOR ", ") as office_phones'),
                    ])
                    ->where('contactable_type', 'Horsefly\\Office')
                    ->whereNotNull('contact_email')
                    ->where('contact_email', '!=', '')
                    ->groupBy('contactable_id'),
                'office_contacts',
                'office_contacts.contactable_id',
                '=',
                'offices.id'
            )
            ->leftJoin('audits as open_audits', 'open_audits.id', '=', 'latest_open_audit_ids.id');

        if ($request->filled('search.value')) {
            $searchTerm = (string) $request->input('search.value');

            // 1. Get Matching IDs from Scout (searches internal Sale columns like postcode, UID, etc.)
            $saleIds = Sale::search($searchTerm)->keys()->toArray();

            // 2. Combine Scout results with direct relationship searches
            $model->where(function ($query) use ($searchTerm, $saleIds) {
                // IDs from Scout
                if (!empty($saleIds)) {
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
        } else if ($typeFilter == 'regular') {
            $model->where('sales.job_type', 'regular');
        }

        // Filter by category if it's not empty
        if ($officeFilter) {
            $model->whereIn('sales.office_id', $officeFilter);
        }

        // CV limit filter — use HAVING on the pre-aggregated cv_counts join
        switch ($limitCountFilter) {
            case 'max':
                // Limit reached: sent CVs == cv_limit
                $model->havingRaw('COALESCE(cv_counts.cv_count, 0) >= sales.cv_limit');
                break;
            case 'not max':
                // Not at limit but has some CVs sent
                $model->havingRaw('COALESCE(cv_counts.cv_count, 0) > 0 AND COALESCE(cv_counts.cv_count, 0) < sales.cv_limit');
                break;
            case 'zero':
                // No CVs sent yet
                $model->havingRaw('COALESCE(cv_counts.cv_count, 0) = 0');
                break;
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
                'office_name' => 'offices.name',
                'unit_name' => 'units.unit_name',
                'job_title' => 'job_titles.name',
                'job_category' => 'job_categories.name',
                'job_source' => 'sales.job_source_id',
                'open_date' => 'sales.open_date',
                'created_at' => 'sales.created_at',
                'updated_at' => 'sales.updated_at',
            ];

            // ❌ Skip non-sortable columns
            $nonSortable = [
                'checkbox',
                'action',
                'sale_notes',
                'cv_limit',
                'position_type'
            ];

            if (in_array($orderColumn, $nonSortable)) {
                $model->orderBy('sales.updated_at', 'desc'); // fallback
            }
            // ✅ If mapped column exists
            elseif (isset($columnMap[$orderColumn])) {
                $model->orderBy($columnMap[$orderColumn], $orderDirection);
            }
            // ✅ Direct DB column (safe fallback)
            elseif (!empty($orderColumn) && $orderColumn !== 'DT_RowIndex') {
                $model->orderBy("sales.$orderColumn", $orderDirection);
            }
            // ✅ Final fallback
            else {
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
                    return $sale->office_name ? ucwords($sale->office_name) : '-';
                })
                ->addColumn('unit_name', function ($sale) {
                    return $sale->unit_name ? ucwords($sale->unit_name) : '-';
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
                    $status = $sale->no_of_sent_cv == $sale->cv_limit ? '<span class="badge w-100 bg-danger" style="font-size:90%" >0/' . $sale->cv_limit . '<br>Limit Reached</span>' : "<span class='badge w-100 bg-primary' style='font-size:90%'>" . ((int) $sale->cv_limit - (int) $sale->no_of_sent_cv . '/' . (int) $sale->cv_limit) . "<br>Limit Remains</span>";
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
                    $notesIndex = !empty($sale->sale_notes) ? $sale->sale_notes : ($sale->latest_note ?? '-');

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
                                    <a href="#" title="View Note" onclick="showNotesModal(\'' . (int) $sale->id . '\',\'' . $notes . '\', \'' . $office_name . '\', \'' . $unit_name . '\', \'' . $postcode . '\')">
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

                    if (Gate::allows('sale-view-manager-details')) {
                        $action .= '<li><a class="dropdown-item" href="javascript:void(0);" onclick="viewManagerDetails(' . (int) $sale->office_id . ')">Manager Details</a></li>';
                    }

                    $action .= '<li><a class="dropdown-item" href="javascript:void(0);" onclick="deleteSale(' . $sale->id . ')">Delete</a></li>';

                    $action .= '</ul></div>';

                    return $action;
                })
                ->addColumn('office_emails', function ($sale) {
                    return $sale->office_emails ? $sale->office_emails : '-';
                })
                ->addColumn('office_phones', function ($sale) {
                    return $sale->office_phones ? $sale->office_phones : '-';
                })
                ->rawColumns(['checkbox', 'office_phones', 'office_emails', 'sale_notes', 'experience', 'position_type', 'sale_postcode', 'qualification', 'job_title', 'cv_limit', 'open_date', 'job_category', 'office_name', 'salary', 'unit_name', 'action', 'statusFilter'])
                ->make(true);
        }
    }

    // scrap destroy
    public function scrappedOfficeDestroy(Request $request)
    {
        try {
            $ids = $request->has('id')
                ? (is_array($request->id) ? $request->id : [$request->id])
                : [];

            $offices = Office::whereIn('id', $ids)->where('status', 4)->get();

            if ($offices->isEmpty()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Office(s) not found'
                ], 404);
            }

            $foundIds = $offices->pluck('id')->toArray();
            $notFoundIds = array_diff($ids, $foundIds);

            DB::beginTransaction();

            Contact::whereIn('contactable_id', $foundIds)
                ->where('contactable_type', Office::class)
                ->forceDelete();

            Sale::whereIn('office_id', $foundIds)
                ->where('status', 4)
                ->forceDelete();

            Unit::whereIn('office_id', $foundIds)
                ->where('status', 4)
                ->forceDelete();

            // Delete the office
            Office::whereIn('id', $foundIds)->where('status', 4)->forceDelete();

            DB::commit();

            $response = [
                'status' => true,
                'message' => count($foundIds) . ' office(s) deleted successfully',
                'deleted' => $foundIds,
            ];

            if (!empty($notFoundIds)) {
                $response['not_found'] = array_values($notFoundIds);
            }

            return response()->json($response);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function scrappedUnitDestroy(Request $request)
    {
        try {
            $ids = $request->has('id')
                ? (is_array($request->id) ? $request->id : [$request->id])
                : [];

            if (empty($ids)) {
                return response()->json([
                    'status' => false,
                    'message' => 'No IDs provided'
                ], 400);
            }

            $units = Unit::whereIn('id', $ids)->where('status', 4)->get();

            if ($units->isEmpty()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unit(s) not found'
                ], 404);
            }

            $foundIds = $units->pluck('id')->toArray();
            $notFoundIds = array_diff($ids, $foundIds);

            DB::beginTransaction();

            Contact::whereIn('contactable_id', $foundIds)
                ->where('contactable_type', Unit::class)
                ->forceDelete();

            Sale::whereIn('unit_id', $foundIds)->where('status', 4)->forceDelete();

            Unit::whereIn('id', $foundIds)->where('status', 4)->forceDelete();

            DB::commit();

            $response = [
                'status' => true,
                'message' => count($foundIds) . ' unit(s) deleted successfully',
                'deleted' => $foundIds,
            ];

            if (!empty($notFoundIds)) {
                $response['not_found'] = array_values($notFoundIds);
            }

            return response()->json($response);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    // public function scrappedSaleDestroy(Request $request)
    // {
    //     try {
    //         $ids = $request->has('id')
    //             ? (is_array($request->id) ? $request->id : [$request->id])
    //             : [];

    //         if (empty($ids)) {
    //             return response()->json([
    //                 'status' => false,
    //                 'message' => 'No IDs provided'
    //             ], 400);
    //         }

    //         $sales = Sale::whereIn('id', $ids)->where('status', 4)->get();

    //         if ($sales->isEmpty()) {
    //             return response()->json([
    //                 'status' => false,
    //                 'message' => 'Sale(s) not found or not scrapped'
    //             ], 404);
    //         }

    //         $foundIds = $sales->pluck('id')->toArray();
    //         $notFoundIds = array_diff($ids, $foundIds);

    //         DB::beginTransaction();

    //         Contact::whereIn('contactable_id', $foundIds)
    //             ->where('contactable_type', Sale::class)
    //             ->forceDelete();

    //         Sale::whereIn('id', $foundIds)->forceDelete();

    //         DB::commit();

    //         $response = [
    //             'status' => true,
    //             'message' => count($foundIds) . ' sale(s) deleted successfully',
    //             'deleted' => $foundIds,
    //         ];

    //         if (!empty($notFoundIds)) {
    //             $response['not_found'] = array_values($notFoundIds);
    //         }

    //         return response()->json($response);
    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         return response()->json([
    //             'status' => false,
    //             'message' => 'Something went wrong',
    //             'error' => $e->getMessage()
    //         ], 500);
    //     }
    // }

    public function scrappedSaleDestroy(Request $request)
    {
        try {
            $ids = $request->has('id')
                ? (is_array($request->id) ? $request->id : [$request->id])
                : [];

            if (empty($ids)) {
                return response()->json([
                    'status' => false,
                    'message' => 'No IDs provided'
                ], 400);
            }

            $sales = Sale::whereIn('id', $ids)->where('status', 4)->get();

            if ($sales->isEmpty()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Sale(s) not found or not scrapped'
                ], 404);
            }

            $foundIds = $sales->pluck('id')->toArray();
            $notFoundIds = array_diff($ids, $foundIds);

            $officeIds = $sales->pluck('office_id')->filter()->unique()->toArray();
            $unitIds = $sales->pluck('unit_id')->filter()->unique()->toArray();

            DB::beginTransaction();

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
                ->forceDelete();

            // Delete office contacts and offices only if no other sales reference them
            if (!empty($soloOfficeIds)) {
                Contact::whereIn('contactable_id', $soloOfficeIds)
                    ->where('contactable_type', Office::class)
                    ->forceDelete();

                Office::whereIn('id', $soloOfficeIds)->forceDelete();
            }

            // Delete units only if no other sales reference them
            if (!empty($soloUnitIds)) {
                Unit::whereIn('id', $soloUnitIds)->forceDelete();
            }

            // Always delete the requested sales
            Sale::whereIn('id', $foundIds)->forceDelete();

            DB::commit();

            $response = [
                'status' => true,
                'message' => count($foundIds) . ' sale(s) deleted successfully',
                'deleted_sales' => $foundIds,
                'deleted_offices' => $soloOfficeIds,  // offices that were also deleted
                'deleted_units' => $soloUnitIds,    // units that were also deleted
            ];

            if (!empty($notFoundIds)) {
                $response['not_found'] = array_values($notFoundIds);
            }

            return response()->json($response);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong',
                'error' => $e->getMessage()
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
                    'message' => 'No sale IDs provided'
                ], 422);
            }

            $sales = Sale::whereIn('id', $ids)->where('status', 4)->get();

            if ($sales->isEmpty()) {
                return response()->json([
                    'status' => false,
                    'message' => 'No scrapped sales found'
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
                    'office_notes' => 'Sale has been approved.'
                ]);

            Unit::whereIn('id', $unitIds)
                ->where('status', 4)
                ->update([
                    'status' => 1,
                    'unit_notes' => 'Sale has been approved.'
                ]);

            Sale::whereIn('id', $foundIds)
                ->where('status', 4)
                ->update([
                    'status' => 1,
                    'sale_notes' => 'Sale has been approved.'
                ]);

            $response = [
                'status' => true,
                'message' => count($foundIds) . ' sale(s) approved successfully',
                'approved' => $foundIds,
            ];

            if (!empty($notFoundIds)) {
                $response['not_found'] = array_values($notFoundIds);
            }

            return response()->json($response);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong',
                'error' => $e->getMessage()
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
                    'message' => 'No unit IDs provided'
                ], 422);
            }

            $units = Unit::whereIn('id', $ids)->where('status', 4)->get();
            if ($units->isEmpty()) {
                return response()->json([
                    'status' => false,
                    'message' => 'No scrapped units found'
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

            if (!empty($notFoundIds)) {
                $response['not_found'] = array_values($notFoundIds);
            }

            return response()->json($response);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong',
                'error' => $e->getMessage()
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
                    'message' => 'No office IDs provided'
                ], 422);
            }

            $offices = Office::whereIn('id', $ids)->where('status', 4)->get();
            if ($offices->isEmpty()) {
                return response()->json([
                    'status' => false,
                    'message' => 'No scrapped offices found'
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

            if (!empty($notFoundIds)) {
                $response['not_found'] = array_values($notFoundIds);
            }

            return response()->json($response);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong',
                'error' => $e->getMessage()
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

            if ($email_template && !empty($email_template->template)) {
                $email_from = $email_template->from_email;

                $replace = [
                    $sale->office->office_name ?? '',
                    $sale->unit->unit_name ?? '',
                    $sale->sale_postcode ?? '',
                    ''
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
                'from_email' => $email_from
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
                'message' => 'No valid emails provided.'
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
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
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

                    if (!$is_save) {
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
                'sent_to' => $success
            ]);
        } elseif (!empty($success)) {
            return response()->json([
                'success' => true,
                'message' => 'Email sent to some recipients.',
                'sent_to' => $success,
                'failed' => $failed
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send email to all recipients.',
                'failed' => $failed
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
            if (!$sale) {
                $failed[] = "Sale ID: {$sale_id} (not found)";
                continue;
            }

            // Guard: office not found
            $office = Office::with('contact')->find($sale->office_id);
            if (!$office) {
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
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
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

                    if (!$is_save) {
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
                'sent_to' => $success
            ]);
        } elseif (!empty($success)) {
            return response()->json([
                'success' => true,
                'message' => 'Email sent to ' . count($success) . ' of ' . (count($success) + count($failed)) . ' recipient(s).',
                'sent_to' => $success,
                'failed' => $failed
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send email to all recipients.',
                'failed' => $failed
            ], 500);
        }
    }
}
