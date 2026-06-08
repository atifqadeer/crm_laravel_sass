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
        // ---------------------------------------------------------------
        // 0. EXTEND PHP RUNTIME LIMITS FOR LONG-RUNNING IMPORT
        // ---------------------------------------------------------------
        if (function_exists('set_time_limit')) {
            set_time_limit(0);
        }

        @ini_set('max_execution_time', '300');
        @ini_set('max_input_time', '-1');
        @ini_set('memory_limit', '512M');
        @ini_set('default_socket_timeout', '300');
        @ini_set('request_terminate_timeout', '300');   // ← FPM pool request timeout

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
            $service = new ScrapService;
            $jobs = $service->runByKey($actorKey, $input);

            if (empty($jobs) || !is_array($jobs)) {
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
            $startTime = microtime(true);
            $maxRequestTime = 25; // Stop everything after 25 seconds to prevent 504

            foreach ($jobChunks as $chunkIndex => $jobChunk) {
                if (microtime(true) - $startTime > $maxRequestTime) {
                    Log::warning('[ScrapImport] Stopping import early due to total request time limit.');
                    break;
                }

                try {
                    $result = match (true) {
                        str_contains($actorKey, 'scrap_apify_indeed') => $this->persistJobsIndeed($jobChunk),
                        str_contains($actorKey, 'scrap_apify_totaljobs') => $this->persistJobsTotalJob($jobChunk),
                        str_contains($actorKey, 'scrap_apify_reed') => $this->persistJobsReed($jobChunk),
                        default => throw new InvalidArgumentException("No persist handler found for actor_key: [{$actorKey}]"),
                    };

                    $importedCount += $result;

                    // Log::info('[ScrapImport] Chunk processed', [
                    //     'actor_key' => $actorKey,
                    //     'chunk_index' => $chunkIndex + 1,
                    //     'total_chunks' => count($jobChunks),
                    //     'chunk_size' => count($jobChunk),
                    //     'imported' => $result,
                    // ]);
                } catch (ConnectionException $e) {
                    // Handle timeout/connection errors
                    $failedChunks[] = [
                        'chunk' => $chunkIndex + 1,
                        'error' => 'Timeout/Connection Error: ' . $e->getMessage(),
                    ];

                    // Log::warning('[ScrapImport] Chunk timeout/connection error', [
                    //     'actor_key' => $actorKey,
                    //     'chunk_index' => $chunkIndex + 1,
                    //     'error' => $e->getMessage(),
                    // ]);
                } catch (Throwable $e) {
                    // Handle other errors
                    $failedChunks[] = [
                        'chunk' => $chunkIndex + 1,
                        'error' => $e->getMessage(),
                    ];

                    // Log::error('[ScrapImport] Chunk processing error', [
                    //     'actor_key' => $actorKey,
                    //     'chunk_index' => $chunkIndex + 1,
                    //     'error' => $e->getMessage(),
                    // ]);
                }
            }

            $response = [
                'success' => true,
                'message' => "Imported {$importedCount} out of {$totalCount} jobs from [{$actorKey}]",
                'imported' => $importedCount,
                'total' => $totalCount,
                'chunks_processed' => count($jobChunks),
            ];

            if (!empty($failedChunks)) {
                $response['failed_chunks'] = $failedChunks;
                $response['warning'] = 'Some chunks failed to process due to timeout or errors';
            }

            return response()->json($response);
        } catch (Throwable $e) {
            // Log::error('[ScrapImport] importJobs failed', [
            //     'actor_key' => $actorKey,
            //     'error' => $e->getMessage(),
            // ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function persistJobsIndeed(array $jobs)
    {
        set_time_limit(0);

        $importedCount       = 0;
        $companyWebsiteCache = [];
        $unitWebsiteCache = [];

        foreach ($jobs as $jobIndex => $job) {

            DB::beginTransaction();

            try {
                // ===============================
                // COMPANY NAME + BASIC FIELDS
                // ===============================
                $companyName = trim($job['companyName'] ?? $job['source'] ?? 'Unknown Company');
                $companyUrl  = $job['companyLinks']['corporateWebsite'] ?? null;
                $emails      = $job['emails'] ?? [];   // job-payload emails — handled in Source 0c only

                $descriptionHtml = $job['descriptionHtml'] ?? null;
                $descriptionText = $job['descriptionText'] ?? '';
                $jobPayloadEmails = $job['emails'] ?? [];
                $companyDesc     = $job['companyDescription']
                    ?? $job['companyBriefDescription']
                    ?? $descriptionText
                    ?? '';

                // ===============================
                // LOCATION
                // ===============================
                $lat         = $job['location']['latitude']   ?? null;
                $lng         = $job['location']['longitude']  ?? null;
                $rawPostcode = $job['location']['postalCode'] ?? null;
                $city        = $job['location']['city']       ?? null;

                $postcode = $rawPostcode ? trim($rawPostcode) : null;

                if (empty($postcode) && !empty($lat) && !empty($lng)) {
                    $postcode = $this->getScrappedPostcodes($lat, $lng);
                }

                $postcode = $postcode ?? 'UNKNOWN';

                // LAT/LNG fallback from postcodes table
                if ($postcode !== 'UNKNOWN' && (!$lat || !$lng)) {
                    $postcodeRow = DB::table('postcodes')
                        ->whereRaw("LOWER(REPLACE(postcode,' ','')) = ?", [
                            strtolower(str_replace(' ', '', $postcode))
                        ])
                        ->first();

                    if (!$postcodeRow) {
                        $postcodeRow = DB::table('outcodepostcodes')
                            ->whereRaw("LOWER(REPLACE(outcode,' ','')) = ?", [
                                strtolower(str_replace(' ', '', $postcode))
                            ])
                            ->first();
                    }

                    if ($postcodeRow) {
                        $lat = $postcodeRow->lat;
                        $lng = $postcodeRow->lng;
                    }
                }

                // ===============================
                // OFFICE — find or create
                // ===============================
                $office = Office::whereRaw(
                    "LOWER(REPLACE(office_name,' ','')) = ?",
                    [strtolower(str_replace(' ', '', $companyName))]
                )->first();

                // These are only populated when office is newly created
                $companyWebsite = $companyUrl;   // safe default for re-used office
                $companyEmails  = [];            // scraped only — job payload handled in Source 0c
                $companyPhones  = [];

                if (!$office) {
                    // Only scrape when the office is truly new
                    if (!$companyUrl) {
                        $cacheKey = strtolower(trim($companyName));
                        if (!isset($companyWebsiteCache[$cacheKey])) {
                            $companyWebsiteCache[$cacheKey] = $this->getScrappedCompanyWebsiteData($companyName);
                        }

                        $scraped        = $companyWebsiteCache[$cacheKey];
                        $companyWebsite = $scraped['company_url']    ?? null;
                        $companyEmails  = $scraped['contact_emails'] ?? [];
                        $companyPhones  = $scraped['contact_phones'] ?? [];
                    } else {
                        $cacheKey = strtolower(trim($companyName));
                        if (!isset($companyWebsiteCache[$cacheKey])) {
                            $companyWebsiteCache[$cacheKey] = $this->getScrappedCompanyWebsiteData($companyName, $companyUrl);
                        }
                        // ✅ ADD THIS:
                        $scraped = $companyWebsiteCache[$cacheKey];
                        $companyWebsite = $scraped['company_url'] ?? $companyUrl;
                        $companyEmails = $scraped['contact_emails'] ?? [];
                        $companyPhones = $scraped['contact_phones'] ?? [];
                    }

                    // Merge job-payload emails into scraped emails
                    foreach ($jobPayloadEmails as $raw) {
                        $e = strtolower(trim($raw));
                        if ($this->isProfessionalEmail($e)) {
                            $companyEmails[] = $e;
                        }
                    }
                    $companyEmails = array_values(array_unique($companyEmails));

                    $office = Office::create([
                        'office_name'     => $companyName,
                        'office_postcode' => $postcode,
                        'user_id'         => Auth::id(),
                        'office_type'     => 'head_office',
                        'office_website'  => $companyWebsite,
                        'office_notes'    => $companyDesc
                            ? $this->sanitizeForMysql(substr($companyDesc, 0, 500))
                            : '',
                        'office_lat'      => $lat,
                        'office_lng'      => $lng,
                        'status'          => 4,
                    ]);
                    $office->update(['office_uid' => md5($office->id)]);
                }

                // ===============================
                // COLLECT OFFICE CONTACTS
                // ===============================
                $contactsList = [];

                // Source 0a: scraped emails — independent of phones
                foreach ($companyEmails as $scrapedEmail) {
                    $email = preg_replace('/\s+/', '', strtolower(trim($scrapedEmail)));

                    // ✅ Skip non-professional emails
                    if (!$this->isProfessionalEmail($email)) {
                        continue;
                    }

                    $contactsList[] = [
                        'contact_name'  => $companyName,
                        'contact_phone' => null,
                        'contact_email' => $email,
                    ];
                }

                // Source 0b: scraped phones — only when no emails were scraped
                if (empty($companyEmails)) {
                    foreach ($companyPhones as $phone) {
                        $cleanPhone = $this->normalizePhone($phone);
                        if ($cleanPhone) {
                            $contactsList[] = [
                                'contact_name'  => $companyName,
                                'contact_phone' => $cleanPhone,
                                'contact_email' => null,
                            ];
                        }
                    }
                }

                // Source 0c: job-payload emails — sole owner, not merged into $companyEmails
                foreach ($emails as $rawEmail) {
                    $email = preg_replace('/\s+/', '', strtolower(trim($rawEmail)));

                    // ✅ Skip non-professional emails
                    if (!$this->isProfessionalEmail($email)) {
                        continue;
                    }

                    $contactsList[] = [
                        'contact_name'  => $companyName,
                        'contact_phone' => null,
                        'contact_email' => $email,
                    ];
                }

                // Source 1: job['contacts'] array
                if (!empty($job['contacts']) && is_array($job['contacts'])) {
                    foreach ($job['contacts'] as $c) {
                        $email = isset($c['contactEmail'])
                            ? preg_replace('/\s+/', '', strtolower(trim($c['contactEmail'])))
                            : null;
                        $name  = isset($c['contactName'])  ? trim($c['contactName'])  : null;
                        $phone = isset($c['contactPhone'])
                            ? preg_replace('/\s+/', '', trim($c['contactPhone']))
                            : null;

                        if ($email && $this->isProfessionalEmail($email)) {
                            $contactsList[] = [
                                'contact_name'  => $name ?? $companyName,
                                'contact_phone' => $phone ? $this->normalizePhone($phone) : null,
                                'contact_email' => $email,
                            ];
                        } elseif ($phone) {
                            $cleanPhone = $this->normalizePhone($phone);
                            if ($cleanPhone) {
                                $contactsList[] = [
                                    'contact_name'  => $name ?? $companyName,
                                    'contact_phone' => $cleanPhone,
                                    'contact_email' => null,
                                ];
                            }
                        }
                    }
                }

                // ── Save office contacts — emails + phones PAIRED in one row ──
                // Build paired list: zip emails and phones together so each row
                // has both an email AND a phone where possible.
                $pairedOfficeContacts = $this->pairEmailsAndPhones(
                    $companyEmails,
                    $companyPhones,
                    $companyName
                );

                // Also extract contacts from description text
                $descriptionContacts = $this->extractContactsFromDescriptionText(
                    $descriptionText,
                    $companyName
                );

                // Also extract from job['contacts'] array
                $jobContacts = [];
                foreach ($job['contacts'] ?? [] as $c) {
                    $email = isset($c['contactEmail'])
                        ? strtolower(preg_replace('/\s+/', '', trim($c['contactEmail'])))
                        : null;
                    $name  = isset($c['contactName']) ? trim($c['contactName']) : $companyName;
                    $phone = isset($c['contactPhone'])
                        ? $this->normalizePhone(trim($c['contactPhone']))
                        : null;

                    if ($email && $this->isProfessionalEmail($email)) {
                        $jobContacts[] = [
                            'contact_name'  => $name,
                            'contact_email' => $email,
                            'contact_phone' => $phone,
                        ];
                    } elseif ($phone) {
                        $jobContacts[] = [
                            'contact_name'  => $name,
                            'contact_email' => null,
                            'contact_phone' => $phone,
                        ];
                    }
                }

                $allOfficeContacts = array_merge($pairedOfficeContacts, $descriptionContacts, $jobContacts);
                $this->saveContactsForMorphable($office->id, Office::class, $allOfficeContacts);

                // ===============================
                // UNIT - WITH PROPER NAME MATCHING CHECK
                // ===============================
                $unitName = null;

                if ($descriptionHtml && preg_match('/<b>Branch:\s*<\/b>\s*([^<]+)/i', $descriptionHtml, $m)) {
                    $unitName = trim(html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
                }
                $unitName = $unitName ?: ($city ?? 'Main Unit');

                $unit = Unit::where('office_id', $office->id)
                    ->whereRaw(
                        "LOWER(REPLACE(unit_name,' ','')) = ?",
                        [strtolower(str_replace(' ', '', trim($unitName)))]
                    )
                    ->whereRaw(
                        "LOWER(REPLACE(unit_postcode,' ','')) = ?",
                        [strtolower(str_replace(' ', '', trim($postcode)))]
                    )
                    ->first();

                if (!$unit) {
                    $unitWebsite = null;
                    $unitEmails  = [];
                    $unitPhones  = [];
                    $unitContacts = [];

                    // =============================================
                    // CRITICAL: Check if unit name EXACTLY MATCHES or is VERY SIMILAR to company name
                    // =============================================
                    $isExactlySame = false;

                    $normalizedUnit = strtolower(preg_replace('/[^a-z0-9]/', '', trim($unitName)));
                    $normalizedCompany = strtolower(preg_replace('/[^a-z0-9]/', '', trim($companyName)));

                    // Check 1: Exact match after normalization
                    if ($normalizedUnit === $normalizedCompany) {
                        $isExactlySame = true;
                        Log::info('[Unit] Unit name EXACTLY matches company name', [
                            'unit' => $unitName,
                            'company' => $companyName,
                            'normalized_unit' => $normalizedUnit,
                            'normalized_company' => $normalizedCompany
                        ]);
                    }

                    // ONLY if names are exactly same or very similar, reuse company data
                    if ($isExactlySame) {
                        Log::info('[Unit] REUSING company data because names match', [
                            'unit' => $unitName,
                            'company' => $companyName,
                            'company_website' => $companyWebsite,
                            'company_emails_count' => count($companyEmails)
                        ]);

                        $unitWebsite = $companyWebsite;
                        $unitEmails = $companyEmails;
                        $unitPhones = $companyPhones;
                        $unitContacts = $this->pairEmailsAndPhones($unitEmails, $unitPhones, $unitName);
                    } else {
                        // =============================================
                        // Names are DIFFERENT - Search by unit name ONLY
                        // =============================================
                        Log::info('[Unit] Names are DIFFERENT - Searching by unit name only', [
                            'unit' => $unitName,
                            'company' => $companyName,
                            'normalized_unit' => $normalizedUnit,
                            'normalized_company' => $normalizedCompany
                        ]);

                        $unitCacheKey = strtolower(trim($unitName));

                        if (!isset($unitWebsiteCache[$unitCacheKey])) {
                            Log::info('[Unit] Calling getScrappedCompanyWebsiteData for unit', [
                                'unit' => $unitName,
                                'searching_by' => 'unit_name_only'
                            ]);

                            // Pass null as known URL to force search by unit name only
                            $unitWebsiteCache[$unitCacheKey] = $this->getScrappedCompanyWebsiteData($unitName, null);
                        }

                        $unitScraped = $unitWebsiteCache[$unitCacheKey];
                        $unitWebsite = $unitScraped['company_url'] ?? null;
                        $unitEmails = $unitScraped['contact_emails'] ?? [];
                        $unitPhones = $unitScraped['contact_phones'] ?? [];

                        // If website found, perform deep contact extraction from unit's website
                        if (!empty($unitWebsite)) {
                            Log::info('[Unit] Performing deep contact extraction from unit website', [
                                'unit' => $unitName,
                                'unit_website' => $unitWebsite
                            ]);

                            $extractedContacts = $this->extractAllContactsFromWebsite($unitWebsite, $unitName);

                            $unitEmails = array_merge($unitEmails, $extractedContacts['emails']);
                            $unitPhones = array_merge($unitPhones, $extractedContacts['phones']);
                            $unitContacts = array_merge($unitContacts, $extractedContacts['paired_contacts']);
                        }

                        // ONLY as last resort, if unit has NO website found, then use company website
                        if (empty($unitWebsite) && !empty($companyWebsite)) {
                            Log::warning('[Unit] Unit has no website found, using company website as LAST RESORT', [
                                'unit' => $unitName,
                                'company_website' => $companyWebsite,
                                'reason' => 'unit_has_no_website'
                            ]);
                            $unitWebsite = $companyWebsite;

                            // But DO NOT copy company contacts - still extract from company website for this unit
                            $extractedContacts = $this->extractAllContactsFromWebsite($companyWebsite, $unitName);
                            $unitEmails = array_merge($unitEmails, $extractedContacts['emails']);
                            $unitPhones = array_merge($unitPhones, $extractedContacts['phones']);
                            $unitContacts = array_merge($unitContacts, $extractedContacts['paired_contacts']);
                        }
                    }

                    // Merge job-payload emails
                    foreach ($jobPayloadEmails as $raw) {
                        $e = strtolower(trim($raw));
                        if ($this->isProfessionalEmail($e)) {
                            $unitEmails[] = $e;
                            $unitContacts[] = [
                                'contact_name' => $unitName,
                                'contact_email' => $e,
                                'contact_phone' => null
                            ];
                        }
                    }

                    // Deduplicate all emails and phones
                    $unitEmails = array_values(array_unique(array_filter($unitEmails)));
                    $unitPhones = array_values(array_unique(array_filter($unitPhones)));

                    // Create final paired contacts if not already created and names are different
                    if (empty($unitContacts)) {
                        $unitContacts = $this->pairEmailsAndPhones($unitEmails, $unitPhones, $unitName);
                    }

                    // Create the unit
                    $unit = Unit::create([
                        'office_id'     => $office->id,
                        'unit_name'     => $unitName,
                        'unit_postcode' => $postcode,
                        'user_id'       => Auth::id(),
                        'unit_website'  => $unitWebsite,
                        'unit_notes'    => 'Scrapped from Indeed',
                        'lat'           => $lat,
                        'lng'           => $lng,
                        'status'        => 4,
                        'unit_uid'      => md5(uniqid()),
                    ]);

                    // Save unit contacts
                    if (!empty($unitContacts)) {
                        $this->saveContactsForMorphable($unit->id, Unit::class, $unitContacts);
                        Log::info('[Unit] Contacts saved successfully', [
                            'unit_id' => $unit->id,
                            'contacts_count' => count($unitContacts)
                        ]);
                    }

                    Log::info('[Unit] Unit created successfully', [
                        'unit_id' => $unit->id,
                        'unit_name' => $unitName,
                        'saved_website' => $unit->unit_website,
                        'contacts_saved' => count($unitContacts)
                    ]);
                } else {
                    Log::info('[Unit] Unit already exists, skipping creation', [
                        'unit_id' => $unit->id,
                        'unit_name' => $unitName
                    ]);
                }

                // ── Job title ─────────────────────────────────────────────────
                $rawTitle = trim(str_replace('-', ' ', $job['title'] ?? 'Generic Job'));
                $jobTitle = JobTitle::firstOrCreate(
                    ['name' => $rawTitle],
                    [
                        'type'            => 'regular',
                        'job_category_id' => 2,
                        'description'     => 'Scrapped from Indeed',
                        'is_active'       => true,
                        'related_titles'  => json_encode([]),
                    ]
                );

                $jobTitleId       = $jobTitle->id;
                $jobCategory      = $jobTitle->job_category_id;
                $jobConditionType = $jobTitle->type;

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
                $jobTypes  = $job['jobType'] ?? [];
                $timing    = count($jobTypes)
                    ? implode(', ', array_map(fn($t) => str_replace('-', ' ', $t), $jobTypes))
                    : 'Not specified';
                $vacancies = $job['numOfCandidates'] ?? 2;
                $benefits  = !empty($job['benefits']) ? implode(', ', $job['benefits']) : 'None';

                // ===============================
                // JOB SOURCE
                // ===============================
                $jobSource = $this->resolveJobSource('indeed');

                if (!$jobSource) {
                    Log::warning('[ScrapImport] JobSource "indeed" not found, skipping job.');
                    DB::rollBack();
                    continue;
                }

                // ===============================
                // DUPLICATE CHECK + INSERT
                // ===============================
                $jobUrl = $job['jobUrl'] ?? null;

                $existingSale = Sale::where('office_id', $office->id)
                    ->where('unit_id', $unit->id)
                    ->where('job_title_id', $jobTitleId)
                    ->whereRaw("REPLACE(sale_postcode,' ','') = ?", [str_replace(' ', '', $postcode)])
                    ->first();

                if (!$existingSale) {
                    $sale = Sale::create([
                        'user_id'         => Auth::id(),
                        'office_id'       => $office->id,
                        'unit_id'         => $unit->id,
                        'job_category_id' => $jobCategory,
                        'job_title_id'    => $jobTitleId,
                        'job_source_id'   => $jobSource->id,
                        'job_type'        => $jobConditionType,
                        'position_type'   => strtolower($timing),
                        'sale_postcode'   => $postcode,
                        'cv_limit'        => $vacancies,
                        'timing'          => $timing,
                        'experience'      => $experience,
                        'salary'          => $job['salary']['salaryText'] ?? '',
                        'benefits'        => $benefits,
                        'qualification'   => $qualification,
                        'sale_notes'      => 'Scrap Indeed Job - ' . $jobUrl,
                        'job_description' => $descriptionText,
                        'lat'             => $lat,
                        'lng'             => $lng,
                        'status'          => 4,
                    ]);

                    $sale->update(['sale_uid' => md5($sale->id)]);
                    $importedCount++;
                }

                DB::commit();
            } catch (Exception $e) {
                DB::rollBack();
                Log::error('[ScrapImport] persistJobsIndeed row error: ' . $e->getMessage(), [
                    'job'       => $job['jobUrl'] ?? 'unknown',
                    'job_index' => $jobIndex + 1,
                ]);
            }
        }

        return $importedCount;
    }

    public function persistJobsTotalJob(array $jobs)
    {
        $importedCount      = 0;
        $companyWebsiteCache = [];  // ← shared cache across jobs

        set_time_limit(0);

        foreach ($jobs as $jobIndex => $job) {

            DB::beginTransaction();

            try {
                // ===============================
                // COMPANY / OFFICE
                // ===============================
                $companyName     = trim(explode("\n", $job['companyName'] ?? 'Unknown Company')[0]);
                $companyUrl      = $job['companyURL'] ?? null;
                $companyDesc     = $job['descriptionText'] ?? 'Scrapped from TotalJobs';
                $descriptionText = $job['descriptionText'] ?? '';
                $descriptionHtml = $job['descriptionHTML'] ?? '';

                // ------------------------------------------------------------------
                // Scrape company website data only when no URL is known
                // ------------------------------------------------------------------
                $companyWebsite = $companyUrl;   // default: use what the job gave us
                $companyEmails  = [];
                $companyPhones  = [];

                if (!$companyUrl) {
                    $cacheKey = strtolower(trim($companyName));

                    if (!isset($companyWebsiteCache[$cacheKey])) {
                        $companyWebsiteCache[$cacheKey] = $this->getScrappedCompanyWebsiteData($companyName, null);
                    }

                    $companyDetails = $companyWebsiteCache[$cacheKey];
                    $companyWebsite = $companyDetails['company_url'] ?? null;
                    $companyEmails  = $companyDetails['contact_emails'] ?? [];   // always array
                    $companyPhones  = $companyDetails['contact_phones'] ?? [];   // always array
                }

                // ===============================
                // PARSE LOCATION STRING
                // ===============================
                $locationRaw = $job['location'] ?? '';
                $postcode    = null;
                $city        = null;
                $lat         = null;
                $lng         = null;

                if (!empty($locationRaw)) {
                    if (preg_match('/([A-Z]{1,2}\d{1,2}[A-Z]?\s*\d[A-Z]{2})$/i', $locationRaw, $pcMatch)) {
                        $postcode = strtoupper(trim($pcMatch[1]));

                        $postcode_query = DB::table('postcodes')
                            ->whereRaw("LOWER(REPLACE(postcode, ' ', '')) = ?", [strtolower(str_replace(' ', '', $postcode))])
                            ->first();

                        if (!$postcode_query) {
                            $postcode_query = DB::table('outcodepostcodes')
                                ->whereRaw("LOWER(REPLACE(outcode, ' ', '')) = ?", [strtolower(str_replace(' ', '', $postcode))])
                                ->first();
                        }

                        if ($postcode_query) {
                            $lat = $postcode_query->lat;
                            $lng = $postcode_query->lng;
                        }
                    }

                    if (preg_match('/^([^,(]+)/', $locationRaw, $cityMatch)) {
                        $city = trim($cityMatch[1]);
                    }
                }

                $postcode = $postcode ?? 'UNKNOWN';

                // ===============================
                // OFFICE — find or create
                // ===============================
                $office = Office::whereRaw('LOWER(office_name) = ?', [strtolower($companyName)])->first();

                if (!$office) {
                    $office = Office::create([
                        'office_name'     => $companyName,
                        'office_postcode' => $postcode,
                        'user_id'         => Auth::id(),
                        'office_type'     => 'head_office',
                        'office_website'  => $companyWebsite,
                        'office_notes'    => $this->sanitizeForMysql(substr($companyDesc, 0, 500)),
                        'office_lat'      => $lat,
                        'office_lng'      => $lng,
                        'status'          => 4,
                    ]);

                    $office->update(['office_uid' => md5($office->id)]);
                }

                // ===============================
                // COLLECT OFFICE CONTACTS
                // ===============================
                $officeContactsList = [];

                // Source 0a: scraped emails (independent of phones)
                foreach ($companyEmails as $scrapedEmail) {
                    $email = preg_replace('/\s+/', '', strtolower(trim($scrapedEmail)));
                    if (!$this->isProfessionalEmail($email)) {
                        continue;
                    }
                    $officeContactsList[] = [
                        'contact_name'  => $companyName,
                        'contact_phone' => null,
                        'contact_email' => $email,
                    ];
                }

                // Source 0b: scraped phones — only when no emails were scraped
                if (empty($companyEmails)) {
                    foreach ($companyPhones as $phone) {
                        $cleanPhone = $this->normalizePhone($phone);
                        if ($cleanPhone) {
                            $officeContactsList[] = [
                                'contact_name'  => $companyName,
                                'contact_phone' => $cleanPhone,
                                'contact_email' => null,
                            ];
                        }
                    }
                }

                // Source 1: extract from description text
                if (!empty($descriptionText)) {
                    // Pattern 1: Name (email@example.com)
                    if (preg_match_all(
                        '/([A-Za-z][A-Za-z\.\'\-]+(?:\s[A-Za-z\.\'\-]+)+)\s*\(\s*([\w\.\-]+@[\w\.\-]+\.\w+)\s*\)/',
                        $descriptionText,
                        $matches,
                        PREG_SET_ORDER
                    )) {
                        foreach ($matches as $m) {
                            $email = preg_replace('/\s+/', '', strtolower(trim($m[2])));
                            if ($this->isProfessionalEmail($email)) {
                                $officeContactsList[] = [
                                    'contact_name'  => trim($m[1]),
                                    'contact_phone' => null,
                                    'contact_email' => $email,
                                ];
                            }
                        }
                    }

                    // Pattern 2: Bare email addresses
                    if (preg_match_all(
                        '/[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}/',
                        $descriptionText,
                        $bareMatches
                    )) {
                        foreach ($bareMatches[0] as $rawEmail) {
                            $email = preg_replace('/\s+/', '', strtolower(trim($rawEmail)));
                            if (!$this->isProfessionalEmail($email)) {
                                continue;
                            }
                            $name = null;
                            if (preg_match(
                                '/([A-Z][a-z]+(?:\s[A-Z][a-z]+)+)\s*[:\-]?\s*' . preg_quote($email, '/') . '/',
                                $descriptionText,
                                $nameMatch
                            )) {
                                $name = trim($nameMatch[1]);
                            }
                            $officeContactsList[] = [
                                'contact_name'  => $name ?? $companyName,
                                'contact_phone' => null,
                                'contact_email' => $email,
                            ];
                        }
                    }

                    // Pattern 3: "Email: ..." or "Contact: ..."
                    if (preg_match_all(
                        '/(?:email|contact|mailto|e-mail)\s*[:\-]?\s*([a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,})/i',
                        $descriptionText,
                        $labeledMatches
                    )) {
                        foreach ($labeledMatches[1] as $rawEmail) {
                            $email = preg_replace('/\s+/', '', strtolower(trim($rawEmail)));
                            if ($this->isProfessionalEmail($email)) {
                                $officeContactsList[] = [
                                    'contact_name'  => $companyName,
                                    'contact_phone' => null,
                                    'contact_email' => $email,
                                ];
                            }
                        }
                    }
                }

                // ===============================
                // SAVE OFFICE CONTACTS (deduplicated)
                // ===============================
                $seen = [];
                foreach ($officeContactsList as $contact) {
                    $email = $contact['contact_email'] ?? null;
                    $cleanPhone = $contact['contact_phone'] ?? null;

                    if ($cleanPhone) {
                        $cleanPhone = preg_replace('/[^\d+]/', '', preg_replace('/\s+/', '', trim($cleanPhone)));
                        $cleanPhone = preg_replace('/\+{2,}/', '+', $cleanPhone);
                    }

                    if (!$email && !$cleanPhone) {
                        continue;
                    }

                    $dedupeKey = ($email ?? '') . '|' . ($cleanPhone ?? '');
                    if (isset($seen[$dedupeKey])) {
                        continue;
                    }
                    $seen[$dedupeKey] = true;

                    $exists = Contact::where('contactable_type', Office::class)
                        ->where(function ($q) use ($email, $cleanPhone) {
                            if ($email)      $q->orWhere('contact_email', $email);
                            if ($cleanPhone) $q->orWhere('contact_phone', $cleanPhone);
                        })
                        ->exists();

                    if (!$exists) {
                        Contact::create([
                            'contactable_id'   => $office->id,
                            'contactable_type' => Office::class,
                            'contact_name'     => $contact['contact_name'],
                            'contact_phone'    => $cleanPhone,
                            'contact_email'    => $email,
                        ]);
                    }
                }

                // ===============================
                // UNIT
                // ===============================
                $unitName = null;

                if ($descriptionHtml && preg_match(
                    '/<b>Branch:\s*<\/b>\s*([^<]+)/i',
                    $descriptionHtml,
                    $branchMatch
                )) {
                    $unitName = trim(html_entity_decode($branchMatch[1], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
                }

                $unitName = $unitName ?: ($city ?? 'Main Unit');

                $unit = Unit::where('office_id', $office->id)
                    ->whereRaw('LOWER(unit_name) = ?', [strtolower(trim($unitName))])
                    ->whereRaw("REPLACE(unit_postcode,' ','') = ?", [str_replace(' ', '', $postcode)])
                    ->first();

                if (!$unit) {
                    $unitWebsite = null;
                    $unitEmails  = [];
                    $unitPhones  = [];

                    // Reuse cache — avoid redundant HTTP scrape
                    if ($unitName && strtolower($unitName) !== strtolower($companyName)) {
                        $unitCacheKey = strtolower(trim($unitName));

                        if (!isset($companyWebsiteCache[$unitCacheKey])) {
                            $companyWebsiteCache[$unitCacheKey] = $this->getScrappedCompanyWebsiteData($unitName);
                        }

                        $unitDetails = $companyWebsiteCache[$unitCacheKey];
                        $unitWebsite = $unitDetails['company_url'] ?? null;
                        $unitEmails  = $unitDetails['contact_emails'] ?? [];   // always array
                        $unitPhones  = $unitDetails['contact_phones'] ?? [];   // always array
                    }

                    $unit = Unit::create([
                        'office_id'     => $office->id,
                        'unit_name'     => $unitName,
                        'unit_postcode' => $postcode,
                        'user_id'       => Auth::id(),
                        'unit_website'  => $unitWebsite,
                        'unit_notes'    => 'Scrapped from TotalJobs',
                        'lat'           => $lat,
                        'lng'           => $lng,
                        'status'        => 4,
                        'unit_uid'      => md5(uniqid()),   // inline — no extra UPDATE query
                    ]);

                    // ------------------------------------------------------------------
                    // Build unit contacts list
                    // ------------------------------------------------------------------
                    $unitContactsList = [];

                    // Scraped emails — independent of phones
                    foreach ($unitEmails as $scrapedEmail) {
                        $email = preg_replace('/\s+/', '', strtolower(trim($scrapedEmail)));
                        if (!$this->isProfessionalEmail($email)) {
                            continue;
                        }
                        $unitContactsList[] = [
                            'contact_name'  => $unitName,
                            'contact_phone' => null,
                            'contact_email' => $email,
                        ];
                    }

                    // Scraped phones — only when no emails exist
                    if (empty($unitEmails)) {
                        foreach ($unitPhones as $phone) {
                            $cleanPhone = $this->normalizePhone($phone);
                            if ($cleanPhone) {
                                $unitContactsList[] = [
                                    'contact_name'  => $unitName,
                                    'contact_phone' => $cleanPhone,
                                    'contact_email' => null,
                                ];
                            }
                        }
                    }

                    // Save unit contacts (deduplicated)
                    $seenUnit = [];
                    foreach ($unitContactsList as $contact) {
                        $email      = $contact['contact_email'] ?? null;
                        $cleanPhone = $contact['contact_phone'] ?? null;

                        if ($cleanPhone) {
                            $cleanPhone = preg_replace('/[^\d+]/', '', preg_replace('/\s+/', '', trim($cleanPhone)));
                            $cleanPhone = preg_replace('/\+{2,}/', '+', $cleanPhone);
                        }

                        if (!$email && !$cleanPhone) {
                            continue;
                        }

                        $dedupeKey = ($email ?? '') . '|' . ($cleanPhone ?? '');
                        if (isset($seenUnit[$dedupeKey])) {
                            continue;
                        }
                        $seenUnit[$dedupeKey] = true;

                        $exists = Contact::where('contactable_type', Unit::class)
                            ->where(function ($q) use ($email, $cleanPhone) {
                                if ($email)      $q->orWhere('contact_email', $email);
                                if ($cleanPhone) $q->orWhere('contact_phone', $cleanPhone);
                            })
                            ->exists();

                        if (!$exists) {
                            Contact::create([
                                'contactable_id'   => $unit->id,
                                'contactable_type' => Unit::class,
                                'contact_name'     => $contact['contact_name'],
                                'contact_phone'    => $cleanPhone,
                                'contact_email'    => $email,
                            ]);
                        }
                    }
                }

                // ===============================
                // JOB TITLE
                // ===============================
                $rawTitle = str_replace('-', ' ', $job['title'] ?? 'Generic Job');
                $jobTitle = JobTitle::where('name', $rawTitle)->first();

                if ($jobTitle) {
                    $jobTitleId        = $jobTitle->id;
                    $jobCategory       = $jobTitle->job_category_id;
                    $jobConditionType  = $jobTitle->type;
                } else {
                    $jobCategory      = 2;
                    $jobConditionType = 'regular';
                    $jobTitle = JobTitle::create([
                        'name'            => $rawTitle,
                        'type'            => $jobConditionType,
                        'job_category_id' => $jobCategory,
                        'description'     => 'Scrapped from TotalJobs',
                        'is_active'       => true,
                        'related_titles'  => json_encode([]),
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
                $jobTypeRaw = $job['jobType'] ?? '';
                $timing     = !empty($jobTypeRaw) ? str_replace('-', ' ', $jobTypeRaw) : 'Not specified';
                $vacancies  = $job['numOfCandidates'] ?? 2;
                $benefits   = isset($job['benefits']) && is_array($job['benefits'])
                    ? implode(', ', $job['benefits'])
                    : 'None';

                // ===============================
                // DUPLICATE CHECK + SALE CREATE
                // ===============================
                $jobUrl    = $job['companyURL'] ?? null;
                $jobRef    = 'Scrap TotalJobs Job - ' . $jobUrl;
                $jobSource = $this->resolveJobSource('total job');

                $existingSale = Sale::where('office_id', $office->id)
                    ->where('unit_id', $unit->id)
                    ->whereRaw("REPLACE(sale_postcode,' ','') = ?", [str_replace(' ', '', $postcode)])
                    ->first();

                if (!$existingSale) {
                    $sale = Sale::create([
                        'user_id'         => Auth::id(),
                        'office_id'       => $office->id,
                        'unit_id'         => $unit->id,
                        'job_category_id' => $jobCategory,
                        'job_title_id'    => $jobTitleId,
                        'job_source_id'   => $jobSource->id,
                        'job_type'        => $jobConditionType,
                        'position_type'   => strtolower($timing),
                        'sale_postcode'   => $postcode,
                        'cv_limit'        => $vacancies,
                        'timing'          => $timing,
                        'experience'      => $experience,
                        'salary'          => $job['salaryRangeRaw'] ?? '',
                        'benefits'        => $benefits,
                        'qualification'   => $qualification,
                        'sale_notes'      => $jobRef,
                        'job_description' => $descriptionText,
                        'lat'             => $lat,
                        'lng'             => $lng,
                        'status'          => 4,
                    ]);

                    $sale->update(['sale_uid' => md5($sale->id)]);
                    $importedCount++;
                }

                DB::commit();
            } catch (Exception $e) {
                DB::rollBack();
                Log::error('[ScrapImport] persistJobsTotalJob row error: ' . $e->getMessage(), [
                    'job'       => $job['jobUrl'] ?? 'unknown',
                    'job_index' => $jobIndex + 1,
                ]);
            }
        }

        return $importedCount;
    }

    public function persistJobsReed(array $jobs)
    {
        $importedCount       = 0;
        $companyWebsiteCache = [];   // shared cache across all jobs

        set_time_limit(0);

        foreach ($jobs as $jobIndex => $job) {

            DB::beginTransaction();

            try {
                // ===============================
                // COMPANY / OFFICE
                // ===============================
                $companyName     = trim($job['company'] ?? $job['ouName'] ?? 'Unknown Company');
                $companyDesc     = $job['description_text'] ?? 'Scrapped from Reed';
                $descriptionText = $job['description_text'] ?? '';
                $descriptionHtml = $job['description_html'] ?? '';

                // Scrape once per unique company name, reuse from cache
                $cacheKey = strtolower(trim($companyName));
                if (!isset($companyWebsiteCache[$cacheKey])) {
                    $companyWebsiteCache[$cacheKey] = $this->getScrappedCompanyWebsiteData($companyName);
                }

                $companyDetails = $companyWebsiteCache[$cacheKey];
                $companyUrl     = $companyDetails['company_url']    ?? null;
                $companyEmails  = $companyDetails['contact_emails'] ?? [];   // always array
                $companyPhones  = $companyDetails['contact_phones'] ?? [];   // always array

                // ===============================
                // LOCATION
                // ===============================
                $locationRaw = $job['location'] ?? null;
                $postcode    = null;
                $city        = null;
                $lat         = null;
                $lng         = null;

                if (!empty($locationRaw)) {
                    if (preg_match('/([A-Z]{1,2}\d{1,2}[A-Z]?\s*\d[A-Z]{2})$/i', $locationRaw, $pcMatch)) {
                        $postcode = strtoupper(trim($pcMatch[1]));

                        $postcode_query = DB::table('postcodes')
                            ->whereRaw("LOWER(REPLACE(postcode, ' ', '')) = ?", [strtolower(str_replace(' ', '', $postcode))])
                            ->first();

                        if (!$postcode_query) {
                            $postcode_query = DB::table('outcodepostcodes')
                                ->whereRaw("LOWER(REPLACE(outcode, ' ', '')) = ?", [strtolower(str_replace(' ', '', $postcode))])
                                ->first();
                        }

                        // Guard: only assign coords when a row was actually found
                        if ($postcode_query) {
                            $lat = $postcode_query->lat;
                            $lng = $postcode_query->lng;
                        }
                    }

                    if (preg_match('/^([^,(]+)/', $locationRaw, $cityMatch)) {
                        $city = trim($cityMatch[1]);
                    }
                }

                $postcode = $postcode ?? 'UNKNOWN';

                // ===============================
                // OFFICE — find or create
                // ===============================
                $office = Office::whereRaw('LOWER(office_name) = ?', [strtolower($companyName)])->first();

                if (!$office) {
                    $office = Office::create([
                        'office_name'     => $companyName,
                        'office_postcode' => $postcode,
                        'user_id'         => Auth::id(),
                        'office_type'     => 'head_office',
                        'office_website'  => $companyUrl,
                        'office_notes'    => $this->sanitizeForMysql(substr($companyDesc, 0, 500)),
                        'office_lat'      => $lat,
                        'office_lng'      => $lng,
                        'status'          => 4,
                    ]);

                    $office->update(['office_uid' => md5($office->id)]);
                }

                // ===============================
                // COLLECT OFFICE CONTACTS
                // ===============================
                $officeContactsList = [];

                // Source 0a: scraped emails — independent of phones
                foreach ($companyEmails as $scrapedEmail) {
                    $email = preg_replace('/\s+/', '', strtolower(trim($scrapedEmail)));
                    if (!$this->isProfessionalEmail($email)) {
                        continue;
                    }
                    $officeContactsList[] = [
                        'contact_name'  => $companyName,
                        'contact_phone' => null,
                        'contact_email' => $email,
                    ];
                }

                // Source 0b: scraped phones — only when no emails found
                if (empty($companyEmails)) {
                    foreach ($companyPhones as $phone) {
                        $cleanPhone = $this->normalizePhone($phone);
                        if ($cleanPhone) {
                            $officeContactsList[] = [
                                'contact_name'  => $companyName,
                                'contact_phone' => $cleanPhone,
                                'contact_email' => null,
                            ];
                        }
                    }
                }

                // Source 1: bare emails extracted from description text
                if (!empty($descriptionText)) {
                    if (preg_match_all(
                        '/[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}/',
                        $descriptionText,
                        $bareMatches
                    )) {
                        foreach ($bareMatches[0] as $rawEmail) {
                            $email = preg_replace('/\s+/', '', strtolower(trim($rawEmail)));
                            if (!$this->isProfessionalEmail($email)) {
                                continue;
                            }
                            $officeContactsList[] = [
                                'contact_name'  => $companyName,
                                'contact_phone' => null,
                                'contact_email' => $email,
                            ];
                        }
                    }
                }

                // ===============================
                // SAVE OFFICE CONTACTS (deduplicated)
                // ===============================
                $seen = [];
                foreach ($officeContactsList as $contact) {
                    $email      = $contact['contact_email'] ?? null;
                    $cleanPhone = $contact['contact_phone'] ?? null;

                    if ($cleanPhone) {
                        $cleanPhone = preg_replace('/[^\d+]/', '', preg_replace('/\s+/', '', trim($cleanPhone)));
                        $cleanPhone = preg_replace('/\+{2,}/', '+', $cleanPhone);
                    }

                    if (!$email && !$cleanPhone) {
                        continue;
                    }

                    $dedupeKey = ($email ?? '') . '|' . ($cleanPhone ?? '');
                    if (isset($seen[$dedupeKey])) {
                        continue;
                    }
                    $seen[$dedupeKey] = true;

                    $exists = Contact::where('contactable_type', Office::class)
                        ->where(function ($q) use ($email, $cleanPhone) {
                            if ($email)      $q->orWhere('contact_email', $email);
                            if ($cleanPhone) $q->orWhere('contact_phone', $cleanPhone);
                        })
                        ->exists();

                    if (!$exists) {
                        Contact::create([
                            'contactable_id'   => $office->id,
                            'contactable_type' => Office::class,
                            'contact_name'     => $contact['contact_name'],
                            'contact_phone'    => $cleanPhone,
                            'contact_email'    => $email,
                        ]);
                    }
                }

                // ===============================
                // UNIT
                // ===============================
                $unitName = null;

                if ($descriptionHtml && preg_match(
                    '/<b>Branch:\s*<\/b>\s*([^<]+)/i',
                    $descriptionHtml,
                    $branchMatch
                )) {
                    $unitName = trim(html_entity_decode($branchMatch[1], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
                }

                $unitName = $unitName ?: ($city ?? 'Main Unit');

                $unit = Unit::where('office_id', $office->id)
                    ->whereRaw('LOWER(unit_name) = ?', [strtolower(trim($unitName))])
                    ->whereRaw("REPLACE(unit_postcode, ' ', '') = ?", [str_replace(' ', '', $postcode)])
                    ->first();

                if (!$unit) {
                    $unitWebsite = null;
                    $unitEmails  = [];
                    $unitPhones  = [];

                    // Reuse cache — skip scrape when unit name matches company
                    if ($unitName && strtolower($unitName) !== strtolower($companyName)) {
                        $unitCacheKey = strtolower(trim($unitName));

                        if (!isset($companyWebsiteCache[$unitCacheKey])) {
                            $companyWebsiteCache[$unitCacheKey] = $this->getScrappedCompanyWebsiteData($unitName);
                        }

                        $unitDetails = $companyWebsiteCache[$unitCacheKey];
                        $unitWebsite = $unitDetails['company_url']    ?? null;
                        $unitEmails  = $unitDetails['contact_emails'] ?? [];   // always array
                        $unitPhones  = $unitDetails['contact_phones'] ?? [];   // always array
                    }

                    $unit = Unit::create([
                        'office_id'     => $office->id,
                        'unit_name'     => $unitName,
                        'unit_postcode' => $postcode,
                        'user_id'       => Auth::id(),
                        'unit_website'  => $unitWebsite,
                        'unit_notes'    => 'Scrapped from Reed',
                        'lat'           => $lat,
                        'lng'           => $lng,
                        'status'        => 4,
                        'unit_uid'      => md5(uniqid()),   // inline — removes extra UPDATE query
                    ]);

                    // Build unit contacts list
                    $unitContactsList = [];

                    // Scraped emails — independent of phones
                    foreach ($unitEmails as $scrapedEmail) {
                        $email = preg_replace('/\s+/', '', strtolower(trim($scrapedEmail)));
                        if (!$this->isProfessionalEmail($email)) {
                            continue;
                        }
                        $unitContactsList[] = [
                            'contact_name'  => $unitName,
                            'contact_phone' => null,
                            'contact_email' => $email,
                        ];
                    }

                    // Scraped phones — only when no emails exist
                    if (empty($unitEmails)) {
                        foreach ($unitPhones as $phone) {
                            $cleanPhone = $this->normalizePhone($phone);
                            if ($cleanPhone) {
                                $unitContactsList[] = [
                                    'contact_name'  => $unitName,
                                    'contact_phone' => $cleanPhone,
                                    'contact_email' => null,
                                ];
                            }
                        }
                    }

                    // Save unit contacts (deduplicated)
                    $seenUnit = [];
                    foreach ($unitContactsList as $contact) {
                        $email      = $contact['contact_email'] ?? null;
                        $cleanPhone = $contact['contact_phone'] ?? null;

                        if ($cleanPhone) {
                            $cleanPhone = preg_replace('/[^\d+]/', '', preg_replace('/\s+/', '', trim($cleanPhone)));
                            $cleanPhone = preg_replace('/\+{2,}/', '+', $cleanPhone);
                        }

                        if (!$email && !$cleanPhone) {
                            continue;
                        }

                        $dedupeKey = ($email ?? '') . '|' . ($cleanPhone ?? '');
                        if (isset($seenUnit[$dedupeKey])) {
                            continue;
                        }
                        $seenUnit[$dedupeKey] = true;

                        $exists = Contact::where('contactable_type', Unit::class)
                            ->where(function ($q) use ($email, $cleanPhone) {
                                if ($email)      $q->orWhere('contact_email', $email);
                                if ($cleanPhone) $q->orWhere('contact_phone', $cleanPhone);
                            })
                            ->exists();

                        if (!$exists) {
                            Contact::create([
                                'contactable_id'   => $unit->id,
                                'contactable_type' => Unit::class,
                                'contact_name'     => $contact['contact_name'],
                                'contact_phone'    => $cleanPhone,
                                'contact_email'    => $email,
                            ]);
                        }
                    }
                }

                // ===============================
                // JOB TITLE
                // ===============================
                $rawTitle = $job['jobTitle'] ?? 'Generic Job';

                $jobTitle = JobTitle::where('name', $rawTitle)->first();

                if (!$jobTitle) {
                    $jobTitle = JobTitle::create([
                        'name'            => $rawTitle,
                        'type'            => 'regular',
                        'job_category_id' => 2,
                        'description'     => 'Scrapped from Reed',
                        'is_active'       => true,
                        'related_titles'  => json_encode([]),
                    ]);
                }

                // ===============================
                // JOB DETAILS
                // ===============================
                $timing = $job['employmentType'] ?? 'Not specified';

                $salaryRaw = $job['salary']
                    ?? (isset($job['salaryMin'], $job['salaryMax'])
                        ? $job['salaryMin'] . ' - ' . $job['salaryMax']
                        : '');

                $salary = trim((string) $salaryRaw) . ' per annum';

                if ($salary && !preg_match('/[£$€]/u', $salary)) {
                    $salary = '£' . $salary;
                }

                // ===============================
                // DESCRIPTION PARSING
                // ===============================
                $qualification = 'Not specified';

                if (!empty($descriptionText)) {
                    preg_match_all('/Level\s*\d+\s+[A-Za-z\s]+qualification[^.,]*/i', $descriptionText, $levelMatches);
                    preg_match_all('/NMC\s+registration[^.,]*/i',                     $descriptionText, $nmcMatches);

                    $all = array_merge($levelMatches[0], $nmcMatches[0]);

                    if (!empty($all)) {
                        $qualification = implode(', ', array_unique(array_map('trim', $all)));
                    }
                }

                $experience = 'Not specified';

                if (
                    !empty($descriptionText) &&
                    preg_match('/(minimum\s+\d+.*?experience.*?)(?:\.|\n)/i', $descriptionText, $m)
                ) {
                    $experience = trim($m[1]);
                }

                // ===============================
                // DUPLICATE CHECK + SALE CREATE
                // ===============================
                $jobUrl    = $job['job_url'] ?? $job['url'] ?? null;
                $jobSource = $this->resolveJobSource('reed');

                $existingSale = Sale::where('sale_notes', 'LIKE', '%' . $jobUrl . '%')->first();

                if (!$existingSale) {
                    $sale = Sale::create([
                        'user_id'         => Auth::id(),
                        'office_id'       => $office->id,
                        'unit_id'         => $unit->id,
                        'job_category_id' => $jobTitle->job_category_id,
                        'job_title_id'    => $jobTitle->id,
                        'job_source_id'   => $jobSource->id ?? null,
                        'job_type'        => $jobTitle->type,
                        'position_type'   => strtolower($timing),
                        'sale_postcode'   => $postcode,
                        'cv_limit'        => 2,
                        'timing'          => $timing,
                        'experience'      => $experience,
                        'salary'          => $salary,
                        'benefits'        => 'N/A',
                        'qualification'   => $qualification,
                        'sale_notes'      => 'Reed Job - ' . $jobUrl,
                        'job_description' => $job['jobDescription'] ?? $descriptionText,
                        'lat'             => $lat,
                        'lng'             => $lng,
                        'status'          => 4,
                    ]);

                    $sale->update(['sale_uid' => md5($sale->id)]);
                    $importedCount++;
                }

                DB::commit();
            } catch (Exception $e) {
                DB::rollBack();
                Log::error('[ScrapImport] persistJobsReed error: ' . $e->getMessage(), [
                    'job'       => $job['jobUrl'] ?? 'unknown',
                    'job_index' => $jobIndex + 1,
                ]);
            }
        }

        return $importedCount;
    }

    /**
     * Extract ALL contacts from a website (multiple pages)
     */
    private function extractAllContactsFromWebsite(string $websiteUrl, string $entityName): array
    {
        $result = [
            'emails' => [],
            'phones' => [],
            'paired_contacts' => []
        ];

        try {
            $htmlCache = [];
            $baseUrl = rtrim($websiteUrl, '/');

            // Pages to check for contact information
            $contactPages = [
                $baseUrl,
                $baseUrl . '/contact',
                $baseUrl . '/contact-us',
                $baseUrl . '/about',
                $baseUrl . '/about-us',
                $baseUrl . '/contact.php',
                $baseUrl . '/contact-us.php',
                $baseUrl . '/enquiries',
                $baseUrl . '/get-in-touch',
                $baseUrl . '/careers',
                $baseUrl . '/recruitment',
            ];

            foreach ($contactPages as $pageUrl) {
                Log::info('[Unit] Extracting contacts from page', ['url' => $pageUrl]);

                $html = $this->fetchHtmlWithCache($pageUrl, $htmlCache);
                if (!$html) {
                    continue;
                }

                // Extract emails
                $emails = $this->extractEmailsFromHtml($html);
                foreach ($emails as $email) {
                    if ($this->isProfessionalEmail($email) && !in_array($email, $result['emails'])) {
                        $result['emails'][] = $email;
                        $result['paired_contacts'][] = [
                            'contact_name' => $entityName,
                            'contact_email' => $email,
                            'contact_phone' => null
                        ];
                    }
                }

                // Extract phones
                $phones = $this->extractPhonesFromHtml($html);
                foreach ($phones as $phone) {
                    $cleanPhone = $this->normalizePhone($phone);
                    if ($cleanPhone && !in_array($cleanPhone, $result['phones'])) {
                        $result['phones'][] = $cleanPhone;

                        // Try to pair with existing email contact
                        $paired = false;
                        foreach ($result['paired_contacts'] as &$contact) {
                            if ($contact['contact_phone'] === null && $contact['contact_email'] !== null) {
                                $contact['contact_phone'] = $cleanPhone;
                                $paired = true;
                                break;
                            }
                        }

                        if (!$paired) {
                            $result['paired_contacts'][] = [
                                'contact_name' => $entityName,
                                'contact_email' => null,
                                'contact_phone' => $cleanPhone
                            ];
                        }
                    }
                }

                // Also extract from mailto: links
                $mailtoLinks = $this->extractMailtoLinks($html);
                foreach ($mailtoLinks as $email) {
                    if ($this->isProfessionalEmail($email) && !in_array($email, $result['emails'])) {
                        $result['emails'][] = $email;
                        $result['paired_contacts'][] = [
                            'contact_name' => $entityName,
                            'contact_email' => $email,
                            'contact_phone' => null
                        ];
                    }
                }
            }

            // Remove duplicates from paired contacts
            $uniqueContacts = [];
            foreach ($result['paired_contacts'] as $contact) {
                $key = md5($contact['contact_email'] . $contact['contact_phone']);
                if (!isset($uniqueContacts[$key])) {
                    $uniqueContacts[$key] = $contact;
                }
            }
            $result['paired_contacts'] = array_values($uniqueContacts);
        } catch (Exception $e) {
            Log::error('[Unit] Error extracting contacts from website', [
                'url' => $websiteUrl,
                'error' => $e->getMessage()
            ]);
        }

        return $result;
    }

    /**
     * Extract emails from HTML content
     */
    private function extractEmailsFromHtml(string $html): array
    {
        $emails = [];

        // Pattern for email addresses
        $patterns = [
            '/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/',
            '/[a-zA-Z0-9._%+-]+@(?:care|nursing|healthcare|home)\./i',
            '/mailto:([a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,})/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match_all($pattern, $html, $matches)) {
                foreach ($matches[1] ?? $matches[0] as $email) {
                    $email = strtolower(trim($email));
                    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        $emails[] = $email;
                    }
                }
            }
        }

        return array_unique($emails);
    }

    /**
     * Extract phone numbers from HTML content
     */
    private function extractPhonesFromHtml(string $html): array
    {
        $phones = [];

        // UK phone number patterns
        $patterns = [
            '/\b(01[0-9]{9})\b/',           // 01xxxxx numbers
            '/\b(02[0-9]{9})\b/',           // 02xxxxx numbers
            '/\b(03[0-9]{9})\b/',           // 03xxxxx numbers
            '/\b(07[0-9]{9})\b/',           // Mobile numbers
            '/\b0[1-9]{10}\b/',              // Generic UK landline
            '/\(\d{4,5}\)\s?\d{5,6}/',       // Numbers with area code in brackets
            '/\+44\s?\d{3,4}\s?\d{3,4}\s?\d{3,4}/', // +44 format
            '/tel:([0-9+\s\(\)]+)/i',        // tel: links
            '/<a[^>]+href="tel:([^"]+)"/i',  // Telephone links
        ];

        foreach ($patterns as $pattern) {
            if (preg_match_all($pattern, $html, $matches)) {
                foreach ($matches[1] ?? $matches[0] as $phone) {
                    $cleanPhone = preg_replace('/[^0-9+]/', '', $phone);
                    if (strlen($cleanPhone) >= 10 && strlen($cleanPhone) <= 13) {
                        $phones[] = $cleanPhone;
                    }
                }
            }
        }

        return array_unique($phones);
    }

    /**
     * Extract mailto: links from HTML
     */
    private function extractMailtoLinks(string $html): array
    {
        $emails = [];

        if (preg_match_all('/mailto:([a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,})/i', $html, $matches)) {
            foreach ($matches[1] as $email) {
                $emails[] = strtolower(trim($email));
            }
        }

        return array_unique($emails);
    }

    /**
     * Extract contacts from HTML string (for description text)
     */
    private function extractContactsFromHtmlString(string $html, string $entityName): array
    {
        $result = [
            'emails' => [],
            'phones' => [],
            'paired_contacts' => []
        ];

        // Extract emails
        $emails = $this->extractEmailsFromHtml($html);
        foreach ($emails as $email) {
            if ($this->isProfessionalEmail($email)) {
                $result['emails'][] = $email;
                $result['paired_contacts'][] = [
                    'contact_name' => $entityName,
                    'contact_email' => $email,
                    'contact_phone' => null
                ];
            }
        }

        // Extract phones
        $phones = $this->extractPhonesFromHtml($html);
        foreach ($phones as $phone) {
            $cleanPhone = $this->normalizePhone($phone);
            if ($cleanPhone) {
                $result['phones'][] = $cleanPhone;

                // Try to pair with existing email
                $paired = false;
                foreach ($result['paired_contacts'] as &$contact) {
                    if ($contact['contact_phone'] === null && $contact['contact_email'] !== null) {
                        $contact['contact_phone'] = $cleanPhone;
                        $paired = true;
                        break;
                    }
                }

                if (!$paired) {
                    $result['paired_contacts'][] = [
                        'contact_name' => $entityName,
                        'contact_email' => null,
                        'contact_phone' => $cleanPhone
                    ];
                }
            }
        }

        return $result;
    }


    private function getScrappedPostcodes(float $lat, float $lng)
    {
        try {
            // https://api.postcodes.io/postcodes?lon=-0.47961&lat=51.5462&limit=1
            $response = Http::withOptions([
                'connect_timeout' => 3,  // ✅ max 3s to establish connection
                'timeout' => 10,  // ✅ max 5s for full response
            ])->get('https://api.postcodes.io/postcodes', [
                'lon' => $lng,
                'lat' => $lat,
                'limit' => 1,
            ]);

            if (!$response->successful()) {
                // Log::warning('[ScrapImport] Postcodes API failed', [
                //     'status' => $response->status(),
                //     'lat' => $lat,
                //     'lng' => $lng,
                // ]);

                return null;
            }

            $data = $response->json();

            return $data['result'][0]['postcode'] ?? null;
        } catch (ConnectionException $e) {
            // ✅ Catches timeout specifically
            // Log::warning('[ScrapImport] Postcodes API timed out', [
            //     'lat' => $lat,
            //     'lng' => $lng,
            // ]);

            return null;
        } catch (Throwable $e) {
            // Log::warning('[ScrapImport] Reverse geocode failed', [
            //     'error' => $e->getMessage(),
            //     'lat' => $lat,
            //     'lng' => $lng,
            // ]);

            return null;
        }
    }

    /**
     * Find company website + scrape contacts.
     * 100% FREE — No API key, no signup, no cost.
     */
    // If more emails than phones, remaining emails get null phone and vice versa.
    // ─────────────────────────────────────────────────────────────────────────────
    private function pairEmailsAndPhones(array $emails, array $phones, string $name): array
    {
        $contacts = [];
        $emails   = array_values(array_unique(array_filter($emails)));
        $phones   = array_values(array_unique(array_filter($phones)));

        $count = max(count($emails), count($phones));

        for ($i = 0; $i < $count; $i++) {
            $email = isset($emails[$i]) ? strtolower(trim($emails[$i])) : null;
            $phone = isset($phones[$i]) ? $this->normalizePhone($phones[$i]) : null;

            // Validate email
            if ($email && !$this->isProfessionalEmail($email)) {
                $email = null;
            }

            // Skip if nothing usable
            if (!$email && !$phone) {
                continue;
            }

            $contacts[] = [
                'contact_name'  => $name,
                'contact_email' => $email,
                'contact_phone' => $phone,
            ];
        }

        return $contacts;
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // saveContactsForMorphable — saves a contacts list for an office or unit,
    // deduplicating by email OR phone against existing DB rows.
    // Handles phone cleaning internally so callers don't need to repeat it.
    // ─────────────────────────────────────────────────────────────────────────────
    private function saveContactsForMorphable(int $morphId, string $morphType, array $contacts): void
    {
        $seen = [];  // in-batch dedup key → true

        foreach ($contacts as $contact) {
            $email = $contact['contact_email'] ?? null;
            $phone = $contact['contact_phone'] ?? null;
            $name  = $contact['contact_name']  ?? '';

            // Normalize email
            if ($email) {
                $email = strtolower(preg_replace('/\s+/', '', trim($email)));
                if (!$this->isProfessionalEmail($email)) {
                    $email = null;
                }
            }

            // Normalize phone
            if ($phone) {
                $phone = preg_replace('/[^\d+]/', '', preg_replace('/\s+/', '', trim($phone)));
                $phone = preg_replace('/\+{2,}/', '+', $phone);
                if (strlen(preg_replace('/\D/', '', $phone)) < 10) {
                    $phone = null;
                }
            }

            if (!$email && !$phone) {
                continue;
            }

            // In-batch dedup — prevents inserting same email/phone twice within this loop
            $dedupeKey = ($email ?? '') . '||' . ($phone ?? '');
            if (isset($seen[$dedupeKey])) {
                continue;
            }
            $seen[$dedupeKey] = true;

            // DB dedup — check if this email or phone already exists for this morphable
            $exists = Contact::where('contactable_type', $morphType)
                ->where(function ($q) use ($email, $phone) {
                    if ($email) $q->orWhere('contact_email', $email);
                    if ($phone) $q->orWhere('contact_phone', $phone);
                })
                ->exists();

            if (!$exists) {
                Contact::create([
                    'contactable_id'   => $morphId,
                    'contactable_type' => $morphType,
                    'contact_name'     => $name,
                    'contact_email'    => $email,
                    'contact_phone'    => $phone,
                ]);

                Log::info('[ScrapImport] Contact saved', [
                    'morph_type' => class_basename($morphType),
                    'morph_id'   => $morphId,
                    'name'       => $name,
                    'email'      => $email,
                    'phone'      => $phone,
                ]);
            }
        }
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // extractContactsFromDescriptionText — pulls name+email pairs from job text
    // Returns same shape as pairEmailsAndPhones so it feeds saveContactsForMorphable
    // ─────────────────────────────────────────────────────────────────────────────
    private function extractContactsFromDescriptionText(string $text, string $fallbackName): array
    {
        if (empty($text)) {
            return [];
        }

        $contacts = [];
        $seen     = [];

        $addContact = function (string $email, ?string $name, ?string $phone) use (&$contacts, &$seen, $fallbackName) {
            $email = strtolower(preg_replace('/\s+/', '', trim($email)));
            if (!$this->isProfessionalEmail($email)) {
                return;
            }
            if (isset($seen[$email])) {
                return;
            }
            $seen[$email] = true;
            $contacts[] = [
                'contact_name'  => $name ?? $fallbackName,
                'contact_email' => $email,
                'contact_phone' => $phone ? $this->normalizePhone($phone) : null,
            ];
        };

        // Pattern 1: "Name (email@example.com)"
        if (preg_match_all(
            '/([A-Za-z][A-Za-z\.\'\-]+(?:\s[A-Za-z\.\'\-]+)+)\s*\(\s*([\w.\-]+@[\w.\-]+\.\w+)\s*\)/i',
            $text,
            $m,
            PREG_SET_ORDER
        )) {
            foreach ($m as $row) {
                $addContact($row[2], trim($row[1]), null);
            }
        }

        // Pattern 2: labeled email lines "Email: info@co.com"
        if (preg_match_all(
            '/(?:email|e-mail|contact|mailto|enquir(?:y|ies))\s*[:\-]?\s*([a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,})/i',
            $text,
            $m
        )) {
            foreach ($m[1] as $email) {
                $addContact($email, null, null);
            }
        }

        // Pattern 3: bare email addresses
        if (preg_match_all('/[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}/', $text, $m)) {
            foreach ($m[0] as $email) {
                // Try to find a nearby name
                $name = null;
                if (preg_match(
                    '/([A-Z][a-z]+(?:\s[A-Z][a-z]+)+)\s*[:\-]?\s*' . preg_quote($email, '/') . '/',
                    $text,
                    $nm
                )) {
                    $name = trim($nm[1]);
                }
                $addContact($email, $name, null);
            }
        }

        return $contacts;
    }

    private function getScrappedCompanyWebsiteData(string $companyName, ?string $knownCompanyUrl = null): array
    {
        $blank = [
            'company_url'    => null,
            'contact_emails' => [],
            'contact_phones' => [],
        ];

        Log::info('[ScrapImport] getScrappedCompanyWebsiteData start', [
            'company' => $companyName,
            'known_url' => $knownCompanyUrl,
        ]);

        try {
            // If known URL provided, use it directly
            if ($knownCompanyUrl) {
                $companyUrl = $this->ensureHttpUrl($knownCompanyUrl);
                if ($companyUrl && !$this->isExcludedCompanyWebsite($companyUrl, $this->getFreeExcludedHosts())) {
                    Log::info('[ScrapImport] Using known URL', ['url' => $companyUrl]);
                    // Still scrape for emails/phones
                    return $this->scrapeWebsiteData($companyUrl, $companyName);
                }
            }

            // METHOD 1: Try findCompanyUrlFree
            $companyUrl = $this->findCompanyUrlFree($companyName);
            if ($companyUrl) {
                $companyUrl = $this->ensureHttpUrl($companyUrl);
                if ($companyUrl && !$this->isExcludedCompanyWebsite($companyUrl, $this->getFreeExcludedHosts())) {
                    Log::info('[ScrapImport] Found URL via findCompanyUrlFree', ['url' => $companyUrl]);
                    return $this->scrapeWebsiteData($companyUrl, $companyName);
                }
            }

            // METHOD 2: Try Google search as fallback
            Log::info('[ScrapImport] Trying Google search fallback', ['company' => $companyName]);
            $companyUrl = $this->findCompanyUrlViaGoogle($companyName);
            if ($companyUrl) {
                $companyUrl = $this->ensureHttpUrl($companyUrl);
                if ($companyUrl && !$this->isExcludedCompanyWebsite($companyUrl, $this->getFreeExcludedHosts())) {
                    Log::info('[ScrapImport] Found URL via Google', ['url' => $companyUrl]);
                    return $this->scrapeWebsiteData($companyUrl, $companyName);
                }
            }

            Log::warning('[ScrapImport] No URL found for company', ['company' => $companyName]);
            return $blank;
        } catch (Throwable $e) {
            Log::error('[ScrapImport] getScrappedCompanyWebsiteData exception', [
                'company' => $companyName,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return $blank;
        }
    }

    /**
     * Helper method to scrape website data once URL is found
     */
    private function scrapeWebsiteData(string $companyUrl, string $companyName): array
    {
        $emails = [];
        $phones = [];
        $htmlCache = [];

        Log::info('[ScrapImport] Scraping website data', ['url' => $companyUrl, 'company' => $companyName]);

        // Extract from home page
        $homeHtml = $this->fetchHtmlWithCache($companyUrl, $htmlCache);
        if ($homeHtml) {
            $homeDetails = $this->extractContactDetailsFromHtml($homeHtml);
            $emails = array_merge($emails, $homeDetails['emails'] ?? []);
            $phones = array_merge($phones, $homeDetails['phones'] ?? []);
        }

        // Find contact pages
        $candidateUrls = $this->defaultContactUrls($companyUrl);
        if ($homeHtml) {
            $candidateUrls = array_merge(
                $candidateUrls,
                $this->discoverContactPageUrls($companyUrl, $homeHtml)
            );
        }

        $candidateUrls = array_values(array_unique(array_filter($candidateUrls)));

        // Prioritize contact pages
        $priority = ['/contact', '/contact-us', '/contacts', '/about', '/about-us', '/team', '/support', '/help', '/enquiry'];

        usort($candidateUrls, function ($a, $b) use ($priority) {
            $aScore = 100;
            $bScore = 100;
            foreach ($priority as $i => $p) {
                if (str_contains(strtolower($a), $p)) $aScore = $i;
                if (str_contains(strtolower($b), $p)) $bScore = $i;
            }
            return $aScore <=> $bScore;
        });

        foreach ($candidateUrls as $url) {
            $url = $this->ensureHttpUrl($url);
            if (!$url || !$this->isSameHost($companyUrl, $url) || rtrim($url, '/') === rtrim($companyUrl, '/')) {
                continue;
            }

            $details = $this->fetchContactDetailsWithHtmlCache($url, $htmlCache);
            $emails = array_merge($emails, $details['emails'] ?? []);
            $phones = array_merge($phones, $details['phones'] ?? []);
        }

        return [
            'company_url'    => $companyUrl,
            'contact_emails' => array_values(array_unique(array_filter($emails))),
            'contact_phones' => array_values(array_unique(array_filter($phones))),
        ];
    }
    /**
     * Find company URL via Google search
     */
    private function findCompanyUrlViaGoogle(string $companyName): ?string
    {
        try {
            $searchQuery = urlencode($companyName . ' official website');
            $googleUrl = "https://www.google.com/search?q={$searchQuery}";

            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $googleUrl,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                CURLOPT_TIMEOUT => 10,
                CURLOPT_SSL_VERIFYPEER => false,
            ]);

            $html = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode === 200 && $html) {
                // Extract first result URL from Google search
                if (preg_match('/<a href="\/url\?q=(https?:\/\/[^"&]+)/', $html, $matches)) {
                    $url = urldecode($matches[1]);
                    // Filter out Google, YouTube, LinkedIn, etc.
                    if (!preg_match('/(google|youtube|linkedin|facebook|twitter|instagram)/i', $url)) {
                        return $url;
                    }
                }
            }
        } catch (Exception $e) {
            Log::warning('[ScrapImport] Google search failed', ['error' => $e->getMessage()]);
        }

        return null;
    }

    /**
     * Clean company name by removing legal suffixes
     */
    private function cleanCompanyName(string $companyName): string
    {
        $suffixes = [
            '/\b(inc|incorporated|llc|ltd|limited|corp|corporation|plc|gmbh|sarl|s\.?r\.?l\.?|s\.?a\.?|s\.?p\.?a\.?)\b\.?/i',
            '/\b(company|co|group|holdings|international|enterprises|solutions|systems|technologies|services)\b\.?/i',
            '/[^\w\s]/', // Remove punctuation
        ];

        $cleaned = $companyName;
        foreach ($suffixes as $pattern) {
            $cleaned = preg_replace($pattern, '', $cleaned);
        }

        // Remove extra spaces
        $cleaned = trim(preg_replace('/\s+/', ' ', $cleaned));

        return $cleaned;
    }

    /**
     * Check if URL exists (doesn't return 404)
     */
    private function urlExists(string $url): bool
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_NOBODY => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 5,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);

        curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return $httpCode >= 200 && $httpCode < 400;
    }


    private function fetchHtmlWithCache(string $url, array &$htmlCache): ?string
    {
        if (isset($htmlCache[$url])) {
            return $htmlCache[$url];
        }

        $html = $this->fetchHtml($url);
        $htmlCache[$url] = $html;

        return $html;
    }

    private function fetchContactDetailsWithHtmlCache(?string $contactPageUrl, array &$htmlCache): array
    {
        $blank = ['emails' => [], 'phones' => []];

        $contactPageUrl = $this->ensureHttpUrl($contactPageUrl);
        if (!$contactPageUrl) {
            return $blank;
        }

        $html = $this->fetchHtmlWithCache($contactPageUrl, $htmlCache);
        if (!$html) {
            return $blank;
        }

        $plainText = $this->htmlToPlainText($html);

        return [
            'emails' => $this->extractEmails($html, $plainText),
            'phones' => $this->extractPhones($html, $plainText),
        ];
    }

    private function findCompanyUrlFree(string $companyName): ?string
    {
        // Log::info('[ScrapImport] findCompanyUrlFree start', ['company' => $companyName]);

        // 1️⃣ Try Google first
        try {
            $url = $this->searchGoogle($companyName);

            if ($url) {
                Log::info('[ScrapImport] Found via Google', ['company' => $companyName, 'url' => $url]);
                return $url;
            }

            Log::info('[ScrapImport] Google returned no result, trying for: ' . $companyName);
        } catch (ConnectionException $e) {
            Log::warning('[ScrapImport] Google timed out, trying for: ' . $companyName);
        } catch (Throwable $e) {
            Log::warning('[ScrapImport] Google failed (' . $e->getMessage() . '), trying for: ' . $companyName);
        }

        return null;
    }

    private function fetchHtml(string $url): ?string
    {
        $url = $this->ensureHttpUrl($url);

        if (!$url) {
            return null;
        }

        // Log::info('[ScrapImport] fetchHtml start', ['url' => $url]);

        try {
            $response = Http::retry(2, 500)
                ->withOptions([
                    'connect_timeout' => 8,
                    'timeout' => 15,
                    'allow_redirects' => [
                        'max' => 5,
                        'strict' => false,
                        'referer' => true,
                    ],
                    'http_errors' => false,
                ])
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
                    'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                    'Accept-Language' => 'en-GB,en;q=0.9',
                    'Cache-Control' => 'no-cache',
                ])
                ->get($url);

            if ($response->status() >= 400) {
                Log::warning('[ScrapImport] fetchHtml failed', [
                    'url' => $url,
                    'status' => $response->status(),
                ]);
                return null;
            }

            $html = (string) $response->body();

            if (trim($html) === '' || strlen($html) < 50) {
                Log::warning('[ScrapImport] fetchHtml empty body', ['url' => $url]);
                return null;
            }

            return $html;
        } catch (Throwable $e) {
            Log::warning('[ScrapImport] fetchHtml exception', [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    private function ensureHttpUrl(?string $url): ?string
    {
        if (!$url) {
            return null;
        }

        $url = trim($url);

        if ($url === '') {
            return null;
        }

        if (!preg_match('#^https?://#i', $url)) {
            $url = 'https://' . ltrim($url, '/');
        }

        return filter_var($url, FILTER_VALIDATE_URL) ? rtrim($url, '/') : null;
    }

    private function isSameHost(string $baseUrl, string $candidateUrl): bool
    {
        $baseHost = strtolower(parse_url($baseUrl, PHP_URL_HOST) ?? '');
        $candidateHost = strtolower(parse_url($candidateUrl, PHP_URL_HOST) ?? '');

        $baseHost = preg_replace('/^www\./', '', $baseHost);
        $candidateHost = preg_replace('/^www\./', '', $candidateHost);

        return $baseHost !== '' && $baseHost === $candidateHost;
    }

    private function defaultContactUrls(string $baseUrl): array
    {
        $parts = parse_url($baseUrl);

        if (empty($parts['scheme']) || empty($parts['host'])) {
            return [];
        }

        $root = $parts['scheme'] . '://' . $parts['host'];

        return [
            $root . '/contact',
            $root . '/contact-us',
            $root . '/contacts',
            $root . '/about',
            $root . '/about-us',
            $root . '/team',
            $root . '/locations',
        ];
    }

    private function searchGoogle(string $query): ?string
    {
        // Log::info('[ScrapImport] searchGoogle start', ['query' => $query]);

        // Add "official site" to improve relevance
        $searchQuery = $query . ' uk official website';
        $url = 'https://www.google.com/search?q=' . urlencode($searchQuery) . '&num=5&hl=en';

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 5,
            CURLOPT_ENCODING       => '',   // ← auto-handles gzip/deflate/br
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_USERAGENT      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
            CURLOPT_HTTPHEADER     => [
                'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                'Accept-Language: en-US,en;q=0.9',
                'Accept-Encoding: gzip, deflate, br',
                'Cache-Control: no-cache',
                'Connection: keep-alive',
                'Upgrade-Insecure-Requests: 1',
            ],
        ]);

        $html      = curl_exec($ch);
        $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            Log::warning('[ScrapImport] Google curl error', ['error' => $curlError, 'query' => $query]);
            return null;
        }

        if (!$html || $httpCode >= 400 || strlen($html) < 200) {
            Log::warning('[ScrapImport] Google bad response', ['http_code' => $httpCode, 'html_len' => strlen($html ?? '')]);
            return null;
        }

        // If content is still binary/compressed despite CURLOPT_ENCODING, try manual decode
        if (strlen($html) > 2 && ord($html[0]) === 31 && ord($html[1]) === 139) {
            $decoded = @gzdecode($html);
            if ($decoded) {
                $html = $decoded;
            } else {
                Log::warning('[ScrapImport] Google gzip decode failed');
                return null;
            }
        }

        // Google blocked us (CAPTCHA page or empty body)
        if (stripos($html, 'detected unusual traffic') !== false || stripos($html, 'captcha') !== false) {
            Log::warning('[ScrapImport] Google returned CAPTCHA/block page for: ' . $query);
            return null;
        }

        $excluded = ['google.', 'youtube.', 'facebook.', 'twitter.', 'linkedin.', 'instagram.', 'wikipedia.'];

        // Pattern 1: /url?q= redirect format (most common in Google results)
        if (preg_match_all('/href="\/url\?q=(https?:\/\/[^&"]+)/i', $html, $matches)) {
            foreach ($matches[1] as $candidate) {
                $candidate = urldecode(trim($candidate));
                if (!filter_var($candidate, FILTER_VALIDATE_URL)) {
                    continue;
                }
                if (!$this->isExcludedDomain($candidate, $excluded) && !$this->isJobBoardOrSocialSite($candidate)) {
                    Log::info('[ScrapImport] Google found URL (pattern 1)', ['url' => $candidate]);
                    return $candidate;
                }
            }
        }

        // Pattern 2: data-href or ping attributes used in newer Google HTML
        if (preg_match_all('/data-href="(https?:\/\/[^"]+)"/i', $html, $matches)) {
            foreach ($matches[1] as $candidate) {
                $candidate = trim($candidate);
                if (!filter_var($candidate, FILTER_VALIDATE_URL)) {
                    continue;
                }
                if (!$this->isExcludedDomain($candidate, $excluded) && !$this->isJobBoardOrSocialSite($candidate)) {
                    Log::info('[ScrapImport] Google found URL (pattern 2)', ['url' => $candidate]);
                    return $candidate;
                }
            }
        }

        // Pattern 3: Any absolute HTTPS href not from excluded domains
        if (preg_match_all('/href="(https?:\/\/[^"]{10,})"/i', $html, $matches)) {
            foreach ($matches[1] as $candidate) {
                $candidate = trim($candidate);
                if (!filter_var($candidate, FILTER_VALIDATE_URL)) {
                    continue;
                }
                if (!$this->isExcludedDomain($candidate, $excluded) && !$this->isJobBoardOrSocialSite($candidate)) {
                    Log::info('[ScrapImport] Google found URL (pattern 3)', ['url' => $candidate]);
                    return $candidate;
                }
            }
        }

        Log::warning('[ScrapImport] Google: no usable URL found', ['query' => $query, 'preview' => substr($html, 0, 300)]);
        return null;
    }

    private function isExcludedDomain(string $url, array $excludedFragments): bool
    {
        $host = strtolower(parse_url($url, PHP_URL_HOST) ?? '');
        foreach ($excludedFragments as $fragment) {
            if (str_contains($host, rtrim($fragment, '.'))) {
                return true;
            }
        }
        return false;
    }

    private function isJobBoardOrSocialSite(string $url): bool
    {
        $skipHosts = [
            'indeed.com',
            'reed.co.uk',
            'totaljobs.com',
            'cv-library.co.uk',
            'monster.co.uk',
            'cwjobs.co.uk',
            'fish4jobs.co.uk',
            'jobsite.co.uk',
            'linkedin.com',
            'facebook.com',
            'twitter.com',
            'x.com',
            'instagram.com',
            'youtube.com',
            'tiktok.com',
            'wikipedia.org',
            'wikimedia.org',
            'glassdoor.com',
            'trustpilot.com',
            'google.com',
            'yell.com',
            'yelp.com',
            'checkatrade.com',
            'companies house.gov.uk',
            'find-and-update.company-information.service.gov.uk',
            'companieshouse.gov.uk',
        ];

        $host = strtolower(parse_url($url, PHP_URL_HOST) ?? '');
        $host = preg_replace('/^www\./', '', $host);

        foreach ($skipHosts as $skip) {
            if ($host === $skip || str_ends_with($host, '.' . $skip)) {
                return true;
            }
        }

        return false;
    }

    private function getFreeExcludedHosts(): array
    {
        // Try to load from DB settings if they exist
        $setting = Setting::where('key', 'serpapi_settings')->first();

        if ($setting && $setting->type === 'json') {
            $settings = json_decode($setting->value, true);
            if (!empty($settings['excluded_hosts'])) {
                return $this->normalizeSerpApiExcludedHosts($settings['excluded_hosts']);
            }
        }

        return [
            'wikipedia.org',
            'wikimedia.org',
            'indeed.com',
            'reed.co.uk',
            'totaljobs.com',
            'linkedin.com',
            'facebook.com',
            'twitter.com',
            'glassdoor.com',
            'yell.com',
            'yelp.com',
        ];
    }

    private function isExcludedCompanyWebsite(string $url, array $excludedHosts = []): bool
    {
        $host = strtolower(parse_url($url, PHP_URL_HOST) ?: '');
        if ($host === '') {
            return true;
        }

        if (!empty($excludedHosts)) {
            $normalizedExcludedHosts = [];
            foreach ($excludedHosts as $excludedHost) {
                $excludedHost = trim((string) $excludedHost);
                if ($excludedHost === '') {
                    continue;
                }

                if (preg_match('#^https?://#i', $excludedHost)) {
                    $excludedHost = parse_url($excludedHost, PHP_URL_HOST) ?: $excludedHost;
                }

                $excludedHost = strtolower($excludedHost);
                $excludedHost = preg_replace('#^www\.?#', '', $excludedHost);
                $excludedHost = rtrim($excludedHost, '/');

                if ($excludedHost !== '') {
                    $normalizedExcludedHosts[] = $excludedHost;
                }
            }

            $normalizedExcludedHosts = array_values(array_unique($normalizedExcludedHosts));

            foreach ($normalizedExcludedHosts as $excludedHost) {
                if ($host === $excludedHost || str_ends_with($host, '.' . $excludedHost)) {
                    return true;
                }
            }
        }

        return false;
    }

    private function extractContactDetailsFromHtml(string $html): array
    {
        // Log::info('[ScrapImport] extractContactDetailsFromHtml start', ['html_length' => strlen($html)]);
        $plainText = $this->htmlToPlainText($html);

        $emails = $this->extractEmails($html, $plainText);
        $phones = $this->extractPhones($html, $plainText);

        // Log::info('[ScrapImport] extractContactDetailsFromHtml result', ['emails' => $emails, 'phones' => $phones]);

        return [
            'emails' => $emails,
            'phones' => $phones,
        ];
    }

    private function htmlToPlainText(string $html): string
    {
        $html = preg_replace('/<(script|style)[^>]*>.*?<\/\1>/is', ' ', $html);
        // Add spacing around block-level elements before stripping tags to prevent text merging
        $html = preg_replace('/<(p|div|li|tr|h[1-6]|table|section|header|footer|aside|nav)[^>]*>/i', "\n$0", $html);
        $html = preg_replace('/<br\s*\/?>/i', "\n", $html);
        $html = preg_replace('/<\/p>|<\/div>|<\/li>|<\/tr>|<\/h[1-6]>/i', "\n", $html);

        $text = strip_tags($html);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/[\r\t]+/', "\n", $text);
        $text = preg_replace('/\n+/', "\n", $text);
        $text = trim($text);

        // Log::info('[ScrapImport] htmlToPlainText result', ['plain_length' => strlen($text)]);
        return $text;
    }

    private function discoverContactPageUrls(string $baseUrl, string $html): array
    {
        // Log::info('[ScrapImport] discoverContactPageUrls start', ['baseUrl' => $baseUrl, 'html_length' => strlen($html)]);
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

        $urls = array_values(array_unique($urls));
        Log::info('[ScrapImport] discoverContactPageUrls result', ['baseUrl' => $baseUrl, 'count' => count($urls), 'urls' => $urls]);
        return $urls;
    }

    private function normalizeUrl(string $href, string $baseUrl): ?string
    {
        $href = trim($href);
        // Log::info('[ScrapImport] normalizeUrl start', ['href' => $href, 'baseUrl' => $baseUrl]);

        if (str_starts_with($href, '//')) {
            $scheme = parse_url($baseUrl, PHP_URL_SCHEME) ?: 'https';
            return $scheme . ':' . $href;
        }

        if (preg_match('#^https?://#i', $href)) {
            return $href;
        }

        $baseParts = parse_url($baseUrl);
        if (empty($baseParts['scheme']) || empty($baseParts['host'])) {
            Log::warning('[ScrapImport] normalizeUrl failed to parse baseUrl', ['href' => $href, 'baseUrl' => $baseUrl]);
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

        $normalized = $baseRoot . $dirname . '/' . ltrim($href, '/');
        // Log::info('[ScrapImport] normalizeUrl result', ['href' => $href, 'normalized' => $normalized]);
        return $normalized;
    }

    private function isProfessionalEmail(string $email): bool
    {
        $email = strtolower(trim($email));

        // Invalid email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        // Words to skip
        $blockedWords = [
            'charity',
            'complaint',
            'experience',
            'yourself',
            'everyone',
            'support',
            'help',
            'hello',
            'hi',
            'noreply',
            'no-reply',
            'donotreply',
            'info',
            'admin',
            'test',
            'example',
            'sample',
            'fake',
            'demo',
            'marketing',
            'newsletter',
            'unsubscribe',
            'career',
            'jobs',
            'hr',
            'recruitment',
            'billing',
            'accounts',
            'service',
            'customerservice',
        ];

        foreach ($blockedWords as $word) {
            if (str_contains($email, $word)) {
                return false;
            }
        }

        return true;
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
        $blank = ['emails' => [], 'phones' => []];

        $contactPageUrl = $this->ensureHttpUrl($contactPageUrl);

        if (!$contactPageUrl) {
            return $blank;
        }

        // Log::info('[ScrapImport] fetchContactDetails start', ['url' => $contactPageUrl]);

        $html = $this->fetchHtml($contactPageUrl);

        if (!$html) {
            return $blank;
        }

        $plainText = $this->htmlToPlainText($html);

        return [
            'emails' => $this->extractEmails($html, $plainText),
            'phones' => $this->extractPhones($html, $plainText),
        ];
    }

    private function extractEmails(string $html, string $plainText): array
    {
        $emails = [];

        $addEmail = function (?string $email) use (&$emails) {
            if (!$email) {
                return;
            }

            $email = rawurldecode($email);
            $email = strtolower(trim($email));
            $email = preg_replace('/^mailto:/i', '', $email);
            $email = explode('?', $email)[0];
            $email = trim($email, " \t\n\r\0\x0B<>()[]{}.,;:");

            if ($this->isValidContactEmail($email) && !in_array($email, $emails, true)) {
                $emails[] = $email;
            }
        };

        $decodedHtml = html_entity_decode(rawurldecode($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        if (preg_match_all('/mailto:([^"\'\s<>]+)/i', $decodedHtml, $matches)) {
            foreach ($matches[1] as $email) {
                $addEmail($email);
            }
        }

        $haystack = $plainText . "\n" . strip_tags($decodedHtml);

        $haystack = preg_replace(
            '/([a-zA-Z0-9._%+\-]+)\s*(?:\[at\]|\(at\)|\sat\s)\s*([a-zA-Z0-9.\-]+)/i',
            '$1@$2',
            $haystack
        );

        $haystack = preg_replace(
            '/([a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+)\s*(?:\[dot\]|\(dot\)|\sdot\s)\s*([a-zA-Z]{2,})/i',
            '$1.$2',
            $haystack
        );

        if (preg_match_all('/[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}/', $haystack, $matches)) {
            foreach ($matches[0] as $email) {
                $addEmail($email);
            }
        }

        // Log::info('[ScrapImport] extractEmails result', ['emails' => $emails]);

        return $emails;
    }

    private function extractPhones(string $html, string $plainText): array
    {
        $phones = [];

        $addPhone = function (?string $rawPhone) use (&$phones) {
            $phone = $this->normalizePhone((string) $rawPhone);

            if ($phone && !in_array($phone, $phones, true)) {
                $phones[] = $phone;
            }
        };

        $decodedHtml = html_entity_decode(rawurldecode($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        if (preg_match_all('/tel:\s*([^"\'<>\s]+)/i', $decodedHtml, $matches)) {
            foreach ($matches[1] as $rawPhone) {
                $addPhone($rawPhone);
            }
        }

        $haystack = $plainText . "\n" . strip_tags($decodedHtml);

        preg_match_all(
            '/(?:phone|tel|telephone|mobile|office|call|contact)\s*[:\-]?\s*((?:\+44|0044|0)\s?(?:\d[\s().-]*){9,10})/i',
            $haystack,
            $labelMatches
        );

        foreach ($labelMatches[1] ?? [] as $rawPhone) {
            $addPhone($rawPhone);
        }

        preg_match_all(
            '/\b((?:\+44|0044|0)\s?(?:\d[\s().-]*){9,10})\b/',
            $haystack,
            $matches
        );

        foreach ($matches[1] ?? [] as $rawPhone) {
            $addPhone($rawPhone);
        }

        // Log::info('[ScrapImport] extractPhones result', ['phones' => $phones]);

        return $phones;
    }

    private function normalizePhone(string $phone): ?string
    {
        $phone = html_entity_decode(rawurldecode($phone), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $phone = preg_replace('/[^\d+]/', '', $phone);

        if (!$phone) {
            return null;
        }

        $phone = preg_replace('/^\+{2,}/', '+', $phone);

        if (str_starts_with($phone, '00')) {
            $phone = '+' . substr($phone, 2);
        }

        if (!str_starts_with($phone, '+') && str_starts_with($phone, '44')) {
            $phone = '+' . $phone;
        }

        $digits = preg_replace('/\D+/', '', $phone);

        if (strlen($digits) < 10 || strlen($digits) > 13) {
            return null;
        }

        return $phone;
    }

    private function isValidContactEmail(string $email): bool
    {
        if (!$this->isProfessionalEmail($email)) {
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
        $blacklistedPrefixes = ['noreply', 'no-reply', 'donotreply', 'mailer', 'bounce', 'postmaster'];
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

    /************************ END OF PRIVATE FUNCTIONS FOR SCRAPPING ******************/

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
                $contactIds = Contact::where('contactable_type', Office::class)
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
                    ->where('contactable_type', Unit::class)
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
            } elseif (!empty($orderColumn) && $orderColumn !== 'DT_RowIndex') {
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
                    if (!$sale->office_emails)
                        return '-';
                    $emails = explode(', ', $sale->office_emails);
                    return implode('<br>', array_map('htmlspecialchars', $emails));
                })
                ->addColumn('office_phones', function ($sale) {
                    if (!$sale->office_phones)
                        return '-';
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
                    $notesIndex = !empty($sale->sale_notes) ? $sale->sale_notes : '-';

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

            if (!empty($notFoundIds)) {
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

            if (!empty($notFoundIds)) {
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
            if (!empty($soloOfficeIds)) {
                Contact::whereIn('contactable_id', $soloOfficeIds)
                    ->where('contactable_type', Office::class)
                    ->delete();

                Office::whereIn('id', $soloOfficeIds)->delete();
            }

            // Delete units only if no other sales reference them
            if (!empty($soloUnitIds)) {
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

            if (!empty($notFoundIds)) {
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

            if (!empty($notFoundIds)) {
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

            if (!empty($notFoundIds)) {
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
            if (!empty($soloOfficeIds)) {
                Contact::withTrashed()
                    ->whereIn('contactable_id', $soloOfficeIds)
                    ->where('contactable_type', Office::class)
                    ->restore();

                Office::withTrashed()
                    ->whereIn('id', $soloOfficeIds)
                    ->restore();
            }

            // Delete units only if no other sales reference them
            if (!empty($soloUnitIds)) {
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

            if (!empty($notFoundIds)) {
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

            if (!empty($notFoundIds)) {
                $response['not_found'] = array_values($notFoundIds);
            }

            return response()->json($response);
        } catch (Exception $e) {
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

            if (!empty($notFoundIds)) {
                $response['not_found'] = array_values($notFoundIds);
            }

            return response()->json($response);
        } catch (Exception $e) {
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

            if (!empty($notFoundIds)) {
                $response['not_found'] = array_values($notFoundIds);
            }

            return response()->json($response);
        } catch (Exception $e) {
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

            if ($email_template && !empty($email_template->template)) {
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
            if (!$this->isProfessionalEmail($email)) {
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
                } catch (Exception $e) {
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
        } elseif (!empty($success)) {
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
                if (!$this->isProfessionalEmail($email)) {
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
                } catch (Exception $e) {
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
        } elseif (!empty($success)) {
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

    private function resolveJobSource(string $name)
    {
        $jobSource = JobSource::whereRaw('LOWER(name) = ?', [strtolower($name)])->first();
        if (!$jobSource) {
            Log::warning('[ScrapImport] JobSource not found, creating', ['name' => $name]);
            $jobSource = JobSource::create([
                'name' => $name,
                'description' => 'Auto-created by scraper',
                'is_active' => 1,
            ]);
        }

        return $jobSource;
    }

    private function getSerpApiSettings(): array
    {
        $setting = Setting::where('key', 'serpapi_settings')->first();

        if ($setting && $setting->type === 'json') {
            $settings = json_decode($setting->value, true);
            return [
                'api_key' => $settings['api_key'] ?? '',
                'engine' => $settings['engine'] ?? 'google',
                'keywords' => $settings['keywords'] ?? 'uk official website',
                'url' => $settings['url'] ?? 'https://serpapi.com/search',
                'excluded_hosts' => $this->normalizeSerpApiExcludedHosts($settings['excluded_hosts'] ?? []),
            ];
        }

        // Fallback to defaults if no settings found
        return [
            'api_key' => '',
            'engine' => 'google',
            'keywords' => 'uk official website',
            'url' => 'https://serpapi.com/search',
            'excluded_hosts' => [
                'wikipedia.org',
                'wikimedia.org',
            ],
        ];
    }

    private function normalizeSerpApiExcludedHosts(array|string|null $excludedHosts): array
    {
        if (is_string($excludedHosts)) {
            $excludedHosts = preg_split('/[\r\n,]+/', $excludedHosts);
        }

        if (!is_array($excludedHosts)) {
            return [];
        }

        $normalized = [];
        foreach ($excludedHosts as $host) {
            $host = trim((string) $host);
            if ($host === '') {
                continue;
            }

            if (preg_match('#^https?://#i', $host)) {
                $parsedHost = parse_url($host, PHP_URL_HOST);
                if ($parsedHost) {
                    $host = $parsedHost;
                }
            }

            $host = strtolower($host);
            $host = preg_replace('#^www\.#', '', $host);
            $host = rtrim($host, '/');

            if ($host !== '') {
                $normalized[] = $host;
            }
        }

        return array_values(array_unique($normalized));
    }
}
