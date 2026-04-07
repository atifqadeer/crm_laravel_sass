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


class ScrapController extends Controller
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
                                $contactsMap[$email] = [
                                    'contact_name' => $name ?? $companyName,
                                    'contact_phone' => null,
                                    'contact_email' => $email,
                                ];
                            }
                        }
                    }

                    // Pattern 2: Any bare email address in text (email@example.com)
                    if (
                        preg_match_all(
                            '/[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}/',
                            $descriptionText,
                            $bareMatches
                        )
                    ) {
                        foreach ($bareMatches[0] as $email) {
                            $email = strtolower(trim($email));

                            if (filter_var($email, FILTER_VALIDATE_EMAIL) && !isset($contactsMap[$email])) {

                                // Try to find a name before the email
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

                                // Fallback to company name if no name found
                                $name = $name ?? $companyName;

                                $contactsMap[$email] = [
                                    'contact_name' => $name,
                                    'contact_phone' => null,
                                    'contact_email' => $email,
                                ];
                            }
                        }
                    }

                    // Pattern 3: "Email: email@example.com" or "Contact: email@example.com"
                    if (
                        preg_match_all(
                            '/(?:email|contact|mailto|e-mail)\s*[:\-]?\s*([a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,})/i',
                            $descriptionText,
                            $labeledMatches
                        )
                    ) {
                        foreach ($labeledMatches[1] as $email) {
                            $email = strtolower(trim($email));

                            if (filter_var($email, FILTER_VALIDATE_EMAIL) && !isset($contactsMap[$email])) {
                                $contactsMap[$email] = [
                                    'contact_name' => $companyName, // fallback to company name
                                    'contact_phone' => null,
                                    'contact_email' => $email,
                                ];
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
                        'unit_notes' => 'Imported from Scraper',
                        'lat' => $lat,
                        'lng' => $lng,
                        'status' => 4,// 4=Scrapped
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
        if ($request->has('order')) {
            $orderColumnIndex = $request->input('order.0.column');
            $orderColumn = $request->input("columns.$orderColumnIndex.data");
            $orderDirection = $request->input('order.0.dir', 'asc');

            if ($orderColumn && $orderColumn !== 'DT_RowIndex') {
                $model->orderBy($orderColumn, $orderDirection);
            } else {
                $model->orderBy('offices.created_at', 'desc');
            }
        } else {
            $model->orderBy('offices.created_at', 'desc');
        }

        if ($request->ajax()) {
            return DataTables::eloquent($model)
                ->addIndexColumn()
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
                ->addColumn('status', function ($office) {
                    return $office->status = 4 ? '<span class="badge bg-success">Inprocess</span>' : 'Scrapped';
                })
                ->addColumn('action', function ($office) {
                    $postcode = $office->formatted_postcode;
                    $status = $office->status ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-secondary">Inactive</span>';
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
                ->rawColumns(['office_name', 'office_notes', 'contact_email', 'office_postcode', 'contact_phone', 'contact_landline', 'office_type', 'status', 'action'])
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

            if ($orderColumn && $orderColumn !== 'DT_RowIndex') {
                // Handle contact.* columns differently if needed
                // $column = str_starts_with($orderColumn, 'contact.') ? 'contacts.' . str_replace('contact.', '', $orderColumn) : 'units.' . $orderColumn;
                $query->orderBy($orderColumn, $orderDirection);
            } else {
                $query->orderBy('units.created_at', 'desc');
            }
        } else {
            $query->orderBy('units.created_at', 'desc');
        }


        /* -------------------------------------------------
        | DataTables Response
        -------------------------------------------------*/
        return DataTables::eloquent($query)
            ->addIndexColumn()

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
            ->addColumn(
                'status',
                fn($u) =>
                $u->status
                ? '<span class="badge bg-success">Active</span>'
                : '<span class="badge bg-secondary">Inactive</span>'
            )
            ->addColumn('action', function ($u) {
                $postcode = $u->formatted_postcode;
                $office_name = $u->office?->office_name ?? '-';
                $status = $u->status
                    ? '<span class="badge bg-success">Active</span>'
                    : '<span class="badge bg-secondary">Inactive</span>';

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
                'unit_notes',
                'contact_email',
                'contact_phone',
                'contact_landline',
                'status',
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
            ])->leftJoin('job_titles', 'sales.job_title_id', '=', 'job_titles.id')
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


        // Sorting logic
        if ($request->has('order')) {
            $orderColumn = $request->input('columns.' . $request->input('order.0.column') . '.data');
            $orderDirection = $request->input('order.0.dir', 'asc');

            // Handle special cases first
            if ($orderColumn === 'job_source') {
                $model->orderBy('sales.job_source_id', $orderDirection);
            } elseif ($orderColumn === 'job_category') {
                $model->orderBy('sales.job_category_id', $orderDirection);
            } elseif ($orderColumn === 'job_title') {
                $model->orderBy('sales.job_title_id', $orderDirection);
            }
            // Default case for valid columns
            elseif ($orderColumn && $orderColumn !== 'DT_RowIndex') {
                $model->orderBy($orderColumn, $orderDirection);
            }
            // Fallback if no valid order column is found
            else {
                $model->orderBy('sales.updated_at', 'desc');
            }
        } else {
            // Default sorting when no order is specified
            $model->orderBy('sales.updated_at', 'desc');
        }

        if ($request->ajax()) {
            return DataTables::eloquent($model)
                ->addIndexColumn() // This will automatically add a serial number to the rows
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
                        $action .= '<li><a class="dropdown-item" href="javascript:void(0);" onclick="viewManagerDetails(' . (int) $sale->unit_id . ')">Manager Details</a></li>';
                    }

                    $action .= '<li><a class="dropdown-item" href="javascript:void(0);" onclick="deleteSale(' . $sale->id . ')">Delete</a></li>';

                    $action .= '</ul></div>';

                    return $action;

                })
                ->rawColumns(['sale_notes', 'experience', 'position_type', 'sale_postcode', 'qualification', 'job_title', 'cv_limit', 'open_date', 'job_category', 'office_name', 'salary', 'unit_name', 'action', 'statusFilter'])
                ->make(true);
        }
    }
    public function scrappedOfficeDestroy($id)
    {
        try {
            $office = Office::where('id', $id)->where('status', 4)->first();

            if (!$office) {
                return response()->json([
                    'status' => false,
                    'message' => 'Office not found'
                ], 404);
            }

            $units = Unit::where('office_id', $office->id)->where('status', 4)->get();
            $unitIds = $units->pluck('id')->toArray();
            Contact::where('contactable_id', $office->id)->where('contactable_type', 'Horsefly\Office')->forceDelete();
            Sale::where('office_id', $office->id)->whereIn('unit_id', $unitIds)->where('status', 4)->forceDelete();
            Unit::where('office_id', $office->id)->where('status', 4)->forceDelete();

            $office->forceDelete();

            return response()->json([
                'status' => true,
                'message' => 'Office deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function scrappedUnitDestroy($id)
    {
        try {
            $unit = Unit::where('id', $id)->where('status', 4)->first();

            if (!$unit) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unit not found'
                ], 404);
            }

            Contact::where('contactable_id', $unit->id)->where('contactable_type', 'Horsefly\Unit')->forceDelete();
            Sale::where('unit_id', $unit->id)->where('status', 4)->forceDelete();

            $unit->forceDelete();

            return response()->json([
                'status' => true,
                'message' => 'Unit deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function scrappedSaleDestroy($id)
    {
        try {
            $sale = Sale::where('id', $id)->where('status', 4)->first();

            if (!$sale) {
                return response()->json([
                    'status' => false,
                    'message' => 'Sale not found'
                ], 404);
            }

            Contact::where('contactable_id', $sale->id)->where('contactable_type', 'Horsefly\Sale')->forceDelete();

            $sale->forceDelete();

            return response()->json([
                'status' => true,
                'message' => 'Sale deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
