<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Horsefly\Setting;
use Horsefly\Office;
use Horsefly\Unit;
use Horsefly\JobTitle;
use Horsefly\Sale;
use Horsefly\Contact;
use Horsefly\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Services\ScrapService;


class ScrapImportController extends Controller
{
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


            $importedCount = $this->persistJobs($jobs, $user);

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
    public function persistJobs(array $jobs, $user): int
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
                    // ->whereRaw("REPLACE(office_postcode,' ','')=?", [str_replace(' ', '', $postcode)])
                    ->first();

                if (!$office) {
                    $office = Office::create([
                        'office_name' => $companyName,
                        'office_postcode' => $postcode,
                        'user_id' => $user->id,
                        'office_type' => 'company',
                        'office_website' => $companyUrl,
                        'office_notes' => substr($companyDesc, 0, 500),
                        'office_lat' => $lat,
                        'office_lng' => $lng,
                        'status' => 1,
                    ]);

                    $office->update(['office_uid' => md5($office->id)]);
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
                        'unit_notes' => 'Imported from Scraper',
                        'lat' => $lat,
                        'lng' => $lng,
                        'status' => 1,
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
                        'description' => 'Imported from Scraper',
                        'is_active' => true,
                        'related_titles' => json_encode([]),
                    ]);

                    $jobTitleId = $jobTitle->id;
                }

                // ===============================
                // DESCRIPTION PARSING
                // ===============================
                $descriptionText = $job['descriptionText'] ?? '';

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
                $timing = count($jobTypes) ? implode(', ', $jobTypes) : 'Not specified';
                $vacancies = $job['numOfCandidates'] ?? 1;
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
                    ->where('sale_notes', 'LIKE', '%' . $jobUrl . '%')
                    ->first();

                if (!$existingSale) {

                    $sale = Sale::create([
                        'user_id' => $user->id,
                        'office_id' => $office->id,
                        'unit_id' => $unit->id,
                        'job_category_id' => $jobCategory,
                        'job_title_id' => $jobTitleId,
                        'job_type' => $jobConditionType,
                        'position_type' => $timing,
                        'sale_postcode' => $postcode,
                        'cv_limit' => $vacancies,
                        'timing' => $timing,
                        'experience' => $experience,
                        'salary' => $job['salary']['salaryText'] ?? '',
                        'benefits' => $benefits,
                        'qualification' => $qualification,
                        'sale_notes' => 'Scrap Job - ' . $jobUrl,
                        'job_description' => $descriptionText,
                        'lat' => $lat,
                        'lng' => $lng,
                        'status' => 2,
                    ]);

                    $sale->update(['sale_uid' => md5($sale->id)]);

                    // Contacts
                    if (!empty($job['contacts'])) {
                        foreach ($job['contacts'] as $c) {
                            if ($c['contactName'] != null) {
                                Contact::create([
                                    'contactable_id' => $sale->id,
                                    'contactable_type' => Sale::class,
                                    'contact_name' => $c['contactName'] ?? null,
                                    'contact_email' => $c['contactEmail'] ?? null,
                                    'contact_phone' => $c['contactPhone'] ?? null,
                                ]);
                            }
                        }
                    }

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
}
