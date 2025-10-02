<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Horsefly\Sale;
use Horsefly\Unit;
use Horsefly\Office;
use Horsefly\ApplicantNote;
use Horsefly\ApplicantPivotSale;
use Horsefly\NotesForRangeApplicant;
use Horsefly\Applicant;
use Horsefly\JobCategory;
use Horsefly\User;
use Horsefly\ModuleNote;
use App\Exports\EmailExport;
use Maatwebsite\Excel\Facades\Excel;
use App\Http\Controllers\Controller;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Builder;
use Exception;
use Carbon\Carbon;
use Horsefly\CrmNote;
use Horsefly\JobTitle;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;

class ResourceController extends Controller
{
    public function __construct()
    {
        //
    }
    public function directIndex()
    {
        $jobCategories = JobCategory::where('is_active', 1)->orderBy('name','asc')->get();
        $jobTitles = JobTitle::where('is_active', 1)->orderBy('name','asc')->get();
        $offices = Office::where('status', 1)->orderBy('office_name','asc')->get();
        $users = User::where('is_active', 1)->orderBy('name','asc')->get();

        return view('resources.direct', compact('jobCategories', 'jobTitles', 'offices', 'users'));
    }
    public function indirectIndex()
    {
        $jobCategories = JobCategory::where('is_active', 1)->orderBy('name','asc')->get();
        $jobTitles = JobTitle::where('is_active', 1)->orderBy('name','asc')->get();

        return view('resources.indirect', compact('jobCategories', 'jobTitles'));
    }
    public function blockedApplicantsIndex()
    {
        $jobCategories = JobCategory::where('is_active', 1)->orderBy('name','asc')->get();
        $jobTitles = JobTitle::where('is_active', 1)->orderBy('name','asc')->get();

        return view('resources.blocked-applicants', compact('jobCategories', 'jobTitles'));
    }
    public function rejectedApplicantsIndex()
    {
        $jobCategories = JobCategory::where('is_active', 1)->orderBy('name','asc')->get();
        $jobTitles = JobTitle::where('is_active', 1)->orderBy('name','asc')->get();

        return view('resources.rejected-applicants', compact('jobCategories', 'jobTitles'));
    }
    public function crmPaidIndex()
    {
        $jobCategories = JobCategory::where('is_active', 1)->orderBy('name','asc')->get();
        $jobTitles = JobTitle::where('is_active', 1)->orderBy('name','asc')->get();

        return view('resources.crm-paid-applicants', compact('jobCategories', 'jobTitles'));
    }
    public function noJobIndex()
    {
        $jobCategories = JobCategory::where('is_active', 1)->orderBy('name','asc')->get();
        $jobTitles = JobTitle::where('is_active', 1)->orderBy('name','asc')->get();

        return view('resources.no-job-applicants', compact('jobCategories', 'jobTitles'));
    }
    public function notInterestedIndex()
    {
        $jobCategories = JobCategory::where('is_active', 1)->orderBy('name','asc')->get();
        $jobTitles = JobTitle::where('is_active', 1)->orderBy('name','asc')->get();

        return view('resources.not-interested-applicants', compact('jobCategories', 'jobTitles'));
    }
    public function categoryWiseApplicantIndex()
    {
        $jobCategories = JobCategory::where('is_active', 1)->orderBy('name','asc')->get();
        $jobTitles = JobTitle::where('is_active', 1)->orderBy('name','asc')->get();

        return view('resources.category-wise-applicants', compact('jobCategories', 'jobTitles'));
    }
    public function getResourcesDirectSales(Request $request)
    {
        $typeFilter = $request->input('type_filter', ''); // Default is empty (no filter)
        $categoryFilter = $request->input('category_filter', ''); // Default is empty (no filter)
        $titleFilter = $request->input('title_filter', ''); // Default is empty (no filter)
        $limitCountFilter = $request->input('cv_limit_filter', ''); // Default is empty (no filter)
        $officeFilter = $request->input('office_filter', ''); // Default is empty (no filter)
        $filterBySaleDate = $request->input('date_range_filter', ''); // Default is empty (no filter)

        $model = Sale::query()
            ->select([
                'sales.*',
                'job_titles.name as job_title_name',
                'job_categories.name as job_category_name',
                'offices.office_name as office_name',
                'units.unit_name as unit_name',
            ])
            ->leftJoin('job_titles', 'sales.job_title_id', '=', 'job_titles.id')
            ->leftJoin('job_categories', 'sales.job_category_id', '=', 'job_categories.id')
            ->leftJoin('offices', 'sales.office_id', '=', 'offices.id')
            ->leftJoin('units', 'sales.unit_id', '=', 'units.id')
            ->with(['jobTitle', 'jobCategory', 'unit', 'office', 'user'])
            ->selectRaw(DB::raw("(SELECT COUNT(*) FROM cv_notes WHERE cv_notes.sale_id = sales.id AND cv_notes.status = 1) as no_of_sent_cv"));

        if ($request->has('search.value')) {
            $searchTerm = (string) $request->input('search.value');

            if (!empty($searchTerm)) {
                $model->where(function ($query) use ($searchTerm) {
                    $likeSearch = "%{$searchTerm}%";

                    $query->whereRaw('LOWER(sales.sale_postcode) LIKE ?', [$likeSearch])
                        ->orWhereRaw('LOWER(sales.experience) LIKE ?', [$likeSearch])
                        ->orWhereRaw('LOWER(sales.timing) LIKE ?', [$likeSearch])
                        ->orWhereRaw('LOWER(sales.job_description) LIKE ?', [$likeSearch])
                        ->orWhereRaw('LOWER(sales.job_type) LIKE ?', [$likeSearch])
                        ->orWhereRaw('LOWER(sales.position_type) LIKE ?', [$likeSearch])
                        ->orWhereRaw('LOWER(sales.cv_limit) LIKE ?', [$likeSearch])
                        ->orWhereRaw('LOWER(sales.salary) LIKE ?', [$likeSearch])
                        ->orWhereRaw('LOWER(sales.benefits) LIKE ?', [$likeSearch])
                        ->orWhereRaw('LOWER(sales.qualification) LIKE ?', [$likeSearch]);

                    // Relationship searches with explicit table names
                    $query->orWhereHas('jobTitle', function ($q) use ($likeSearch) {
                        $q->where('job_titles.name', 'LIKE', "%{$likeSearch}%");
                    });

                    $query->orWhereHas('jobCategory', function ($q) use ($likeSearch) {
                        $q->where('job_categories.name', 'LIKE', "%{$likeSearch}%");
                    });

                    $query->orWhereHas('unit', function ($q) use ($likeSearch) {
                        $q->where('units.unit_name', 'LIKE', "%{$likeSearch}%");
                    });

                    $query->orWhereHas('office', function ($q) use ($likeSearch) {
                        $q->where('offices.office_name', 'LIKE', "%{$likeSearch}%");
                    });
                });
            }
        }

        // Filter by type if it's not empty
        switch($typeFilter){
            case 'specialist':
                $model->where('sales.job_type', 'specialist');
                break;
            case 'regular':
                $model->where('sales.job_type', 'regular');
                break;
        }

        if($filterBySaleDate){
            [$start_date, $end_date] = explode('|', $filterBySaleDate);
            $start_date = Carbon::parse(trim($start_date))->startOfDay();
            $end_date = Carbon::parse(trim($end_date))->endOfDay();
        
            $model->whereBetween('sales.created_at', [$start_date, $end_date]);
        }
       
        // Filter by category if it's not empty
        if ($officeFilter) {
            $model->whereIn('sales.office_id', $officeFilter);
        }
        
        // Filter by category if it's not empty
        if ($limitCountFilter) {
            if ($limitCountFilter == 'zero') {
                $model->where('sales.cv_limit', '=', function ($query) {
                    $query->select(DB::raw('count(cv_notes.sale_id) AS sent_cv_count 
                        FROM cv_notes WHERE cv_notes.sale_id=sales.id 
                        AND cv_notes.status = 1'
                    ));
                });
            } elseif ($limitCountFilter == 'not max') {
                $model->where('sales.cv_limit', '>', function ($query) {
                    $query->select(DB::raw('count(cv_notes.sale_id) AS sent_cv_count 
                        FROM cv_notes WHERE cv_notes.sale_id=sales.id 
                        AND cv_notes.status = 1 HAVING sent_cv_count > 0 
                        AND sent_cv_count <> sales.cv_limit'
                    ));
                });
            } elseif ($limitCountFilter == 'max') {
                $model->where('sales.cv_limit', '>', function ($query) {
                    $query->select(DB::raw('count(cv_notes.sale_id) AS sent_cv_count 
                        FROM cv_notes WHERE cv_notes.sale_id=sales.id 
                        AND cv_notes.status = 1 HAVING sent_cv_count = 0'
                    ));
                });
            }
        }
       
        // Filter by category if it's not empty
        if ($categoryFilter) {
            $model->whereIn('sales.job_category_id', $categoryFilter);
        }
       
        // Filter by category if it's not empty
        if ($titleFilter) {
            $model->whereIn('sales.job_title_id', $titleFilter);
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
                $model->orderBy('sales.created_at', 'desc');
            }
        } else {
            // Default sorting when no order is specified
            $model->orderBy('sales.created_at', 'desc');
        }

        if ($request->ajax()) {
            return DataTables::eloquent($model)
                ->addIndexColumn() // This will automatically add a serial number to the rows
                ->addColumn('office_name', function ($sale) {
                    $office_name = $sale->office_name;
                    return $office_name ? ucwords($office_name) : '-';
                })
                ->addColumn('unit_name', function ($sale) {
                    $unit_name = $sale->unit_name;
                    return $unit_name ? ucwords($unit_name) : '-';
                })
                ->addColumn('job_title', function ($sale) {
                    return $sale->jobTitle ? strtoupper($sale->jobTitle->name) : '-';
                })
                ->addColumn('job_category', function ($sale) {
                    $type = $sale->job_type;
                    $stype  = $type && $type == 'specialist' ? '<br>(' . ucwords('Specialist') . ')' : '';
                    return $sale->jobCategory ? ucwords($sale->jobCategory->name) . $stype : '-';
                })
                ->addColumn('sale_postcode', function ($sale) {
                    if($sale->lat != null && $sale->lng != null){
                        $url = url('/sales/fetch-applicants-by-radius/'. $sale->id . '/15');
                        $button = '<a href="'. $url .'" style="color:blue;" target="_blank">'. $sale->formatted_postcode .'</a>'; // Using accessor
                    }else{
                        $button = $sale->formatted_postcode;
                    }
                    return $button;
                })
                ->addColumn('created_at', function ($sale) {
                    return $sale->formatted_created_at; // Using accessor
                })
                ->addColumn('cv_limit', function ($sale) {
                    $status = $sale->no_of_sent_cv == $sale->cv_limit ? '<span class="badge w-100 bg-danger" style="font-size:90%" >' . $sale->no_of_sent_cv . '/' . $sale->cv_limit . '<br>Limit Reached</span>' : "<span class='badge w-100 bg-primary' style='font-size:90%'>" . ((int)$sale->cv_limit - (int)$sale->no_of_sent_cv . '/' . (int)$sale->cv_limit) . "<br>Limit Remains</span>";
                    return $status;
                })
                ->addColumn('qualification', function ($sale) {
                    $fullHtml = $sale->qualification; // HTML from Summernote
                    $id = 'qua-' . $sale->id;

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

                    // 5. Limit preview characters
                    $preview = Str::limit(trim($normalizedText), 80);

                    // 6. Convert newlines to <br>
                    $shortText = nl2br($preview);

                    return '
                        <a href="#"
                        data-bs-toggle="modal"
                        data-bs-target="#' . $id . '">'
                        . $shortText . '
                        </a>

                        <div class="modal fade" id="' . $id . '" tabindex="-1" aria-labelledby="' . $id . '-label" aria-hidden="true">
                            <div class="modal-dialog modal-lg modal-dialog-scrollable">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="' . $id . '-label">Sale Qualification</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        ' . $fullHtml . '
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-dark" data-bs-dismiss="modal">Close</button>
                                    </div>
                                </div>
                            </div>
                        </div>';
                })
                ->addColumn('experience', function ($sale) {
                    $fullHtml = $sale->experience; // HTML from Summernote
                    $id = 'exp-' . $sale->id;

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

                    // 5. Limit preview characters
                    $preview = Str::limit(trim($normalizedText), 80);

                    // 6. Convert newlines to <br>
                    $shortText = nl2br($preview);

                    return '
                        <a href="#"
                        data-bs-toggle="modal"
                        data-bs-target="#' . $id . '">'
                        . $shortText . '
                        </a>

                        <div class="modal fade" id="' . $id . '" tabindex="-1" aria-labelledby="' . $id . '-label" aria-hidden="true">
                            <div class="modal-dialog modal-lg modal-dialog-scrollable">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="' . $id . '-label">Sale Experience</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        ' . $fullHtml . '
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-dark" data-bs-dismiss="modal">Close</button>
                                    </div>
                                </div>
                            </div>
                        </div>';
                })
                ->addColumn('salary', function ($sale) {
                    $fullHtml = $sale->salary; // HTML from Summernote
                    $id = 'slry-' . $sale->id;

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

                    // 5. Limit preview characters
                    $preview = Str::limit(trim($normalizedText), 80);

                    // 6. Convert newlines to <br>
                    $shortText = nl2br($preview);

                    return '
                        <a href="#"
                        data-bs-toggle="modal"
                        data-bs-target="#' . $id . '">'
                        . $shortText . '
                        </a>

                        <div class="modal fade" id="' . $id . '" tabindex="-1" aria-labelledby="' . $id . '-label" aria-hidden="true">
                            <div class="modal-dialog modal-lg modal-dialog-scrollable">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="' . $id . '-label">Sale Salary</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        ' . $fullHtml . '
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-dark" data-bs-dismiss="modal">Close</button>
                                    </div>
                                </div>
                            </div>
                        </div>';
                })
                ->addColumn('action', function ($sale) {
                    $is_disable = ($sale->lat == null || $sale->lng == null);

                    $url = route('emails.sendemailstoapplicants', ['sale_id' => $sale->id]);

                    if ($is_disable) {
                        // Disabled button (not a link)
                        $action = '<button class="btn btn-sm btn-success" disabled title="Coordinates missing" style="width:150px">
                                    <iconify-icon icon="mdi:email-send-outline" class="align-middle"></iconify-icon> Send Email
                                </button>';
                    } else {
                        // Active link styled as button
                        $action = '<a href="'. $url .'" title="Send Email" style="width:150px" class="btn btn-sm btn-success">
                                    <iconify-icon icon="mdi:email-send-outline" class="align-middle"></iconify-icon> Send Email
                                </a>';
                    }

                    return '<div class="btn-group dropstart">' . $action . '</div>';
                })

                ->rawColumns(['sale_notes', 'experience', 'salary', 'qualification', 'sale_postcode', 'job_title', 'cv_limit', 'open_date', 'job_category', 'office_name', 'unit_name', 'status', 'action', 'statusFilter'])
                ->make(true);
        }
    }
    public function getResourcesIndirectApplicants(Request $request)
    {
        // Validate query parameters
        $validated = $request->validate([
            'updated_sales_filter' => 'boolean',
            'category_filter' => 'nullable|string|in:nurse,nonnurse,specialist,chef,nursery',
            'status' => 'nullable|string|in:all,active,disable,open,paid',
            'date_range_filter' => 'nullable|string|regex:/^\d{4}-\d{2}-\d{2}\|\d{4}-\d{2}-\d{2}$/',
            'order.0.column' => 'nullable|integer',
            'order.0.dir' => 'nullable|string|in:asc,desc',
        ]);

        $filterByUpdatedSale = $validated['updated_sales_filter'] ?? false;
        $categoryFilter = $validated['category_filter'] ?? '';
        $status = $validated['status'] ?? 'all';
        $dateRange = $validated['date_range_filter'] ?? Carbon::today()->format('Y-m-d') . '|' . Carbon::today()->format('Y-m-d');
        $radius = 15; // in kilometers
        $orderColumnIndex = $request->input('order.0.column', 0);
        $orderDirection = $request->input('order.0.dir', 'asc');

        // Map DataTables column index to database column
        $columns = ['applicant_name', 'applicant_postcode', 'applicant_job_title', 'updated_at'];
        $orderColumn = $columns[$orderColumnIndex] ?? 'applicant_name';

        // Parse date range
        try {
            [$start_date, $end_date] = explode('|', $dateRange);
            $start_date = Carbon::parse($start_date)->startOfDay();
            $end_date = Carbon::parse($end_date)->endOfDay();
        } catch (\Exception $e) {
            Log::warning('Invalid date range filter: ' . $dateRange, ['error' => $e->getMessage()]);
            $start_date = Carbon::today()->startOfDay();
            $end_date = Carbon::today()->endOfDay();
        }

        // Sales Query
        $salesQuery = Sale::query()
            ->select([
                'sales.id',
                'sales.created_at',
                'sales.lat',
                'sales.lng',
                'sales.job_title_id',
                DB::raw('COALESCE(audits.updated_at, NULL) AS audit_updated_at'),
            ])
            ->where('status', 1)
            ->where('is_on_hold', 0)
            ->leftJoin('audits', function ($join) {
                $join->on('audits.auditable_id', '=', 'sales.id')
                    ->where('audits.auditable_type', '=', 'Horsefly\\Sale')
                    ->where('audits.message', 'like', '%sale-opened%');
            })
            ->where(function ($query) use ($filterByUpdatedSale, $start_date, $end_date) {
                $query->where(function ($q) use ($start_date, $end_date) {
                    $q->whereNotNull('audits.updated_at')
                        ->whereBetween('audits.updated_at', [$start_date, $end_date]);
                })
                ->orWhere(function ($q) use ($filterByUpdatedSale, $start_date, $end_date) {
                    $column = $filterByUpdatedSale ? 'sales.updated_at' : 'sales.created_at';
                    $q->whereBetween($column, [$start_date, $end_date]);
                });
            })
            ->distinct('sales.id');

        $salesData = $salesQuery->get();
        Log::info('Sales fetched', ['count' => $salesData->count(), 'date_range' => $dateRange]);

        // Fetch applicants
        $nearbyApplicants = collect();
        $salesData->chunk(100)->each(function ($chunk) use (&$nearbyApplicants, $radius, $status, $categoryFilter, $orderColumn, $orderDirection) {
            foreach ($chunk as $sale) {
                $applicants = $this->getApplicantsAgainstSales(
                    $sale->lat,
                    $sale->lng,
                    $radius,
                    $sale->job_title_id,
                    $status,
                    $categoryFilter,
                    $orderColumn,
                    $orderDirection
                );
                $nearbyApplicants = $nearbyApplicants->merge($applicants);
                Log::info('Applicants fetched for sale', [
                    'sale_id' => $sale->id,
                    'applicant_count' => count($applicants),
                ]);
            }
        });

        $nearbyApplicants = $nearbyApplicants->unique('id')->sortBy($orderColumn, SORT_REGULAR, $orderDirection === 'desc')->values();
        Log::info('Total unique applicants', ['count' => $nearbyApplicants->count()]);

        return DataTables::of($nearbyApplicants)
            ->with('total_sale_count', $salesData->count())
            ->addColumn('updated_at', function ($applicant) {
                return Carbon::parse($applicant->updated_at)->toFormattedDateString();
            })
            ->addColumn('updated_time', function ($applicant) {
                return Carbon::parse($applicant->updated_at)->format('h:i A');
            })
            ->editColumn('applicant_name', function ($applicant) {
                return e(ucwords($applicant->applicant_name));
            })
            ->editColumn('applicant_postcode', function ($applicant) {
                $statusValue = $this->getApplicantStatus($applicant);
                $postcode = e(strtoupper($applicant->applicant_postcode));
                return in_array($statusValue, ['open', 'reject'])
                    ? '<a href="' . route('available-jobs', $applicant->id) . '">' . $postcode . '</a>'
                    : $postcode;
            })
            ->editColumn('applicant_notes', function ($applicant) {
                $statusValue = $this->getApplicantStatus($applicant);
                $notes = e($applicant->module_notes_details ?? $applicant->applicant_notes ?? '');
                if ($applicant->is_blocked == 0 && in_array($statusValue, ['open', 'reject'])) {
                    $content = '<a href="#" class="reject_history" data-applicant="' . $applicant->id . '"
                            data-controls-modal="#clear_cv' . $applicant->id . '"
                            data-backdrop="static" data-keyboard="false" data-toggle="modal"
                            data-target="#clear_cv' . $applicant->id . '">' . $notes . '</a>';
                    $content .= '<div id="clear_cv' . $applicant->id . '" class="modal fade" tabindex="-1">';
                    $content .= '<div class="modal-dialog modal-lg">';
                    $content .= '<div class="modal-content">';
                    $content .= '<div class="modal-header">';
                    $content .= '<h5 class="modal-title">Notes</h5>';
                    $content .= '<button type="button" class="btn btn-link" data-dismiss="modal">×</button>';
                    $content .= '</div>';
                    $content .= '<form action="' . route('block_or_casual_notes') . '" method="POST" id="app_notes_form' . $applicant->id . '" class="form-horizontal">';
                    $content .= csrf_field();
                    $content .= '<div class="modal-body">';
                    $content .= '<div id="app_notes_alert' . $applicant->id . '"></div>';
                    $content .= '<div id="sent_cv_alert' . $applicant->id . '"></div>';
                    $content .= '<div class="form-group row">';
                    $content .= '<label class="col-form-label col-sm-3">Details</label>';
                    $content .= '<div class="col-sm-9">';
                    $content .= '<input type="hidden" name="applicant_hidden_id" value="' . $applicant->id . '">';
                    $content .= '<input type="hidden" name="applicant_page' . $applicant->id . '" value="7_days_applicants">';
                    $content .= '<textarea name="details" id="sent_cv_details' . $applicant->id . '" class="form-control" cols="30" rows="4" placeholder="TYPE HERE.." required></textarea>';
                    $content .= '</div>';
                    $content .= '</div>';
                    $content .= '<div class="form-group row">';
                    $content .= '<label class="col-form-label col-sm-3">Choose type:</label>';
                    $content .= '<div class="col-sm-9">';
                    $content .= '<select name="reject_reason" class="form-control crm_select_reason" id="reason' . $applicant->id . '">';
                    $content .= '<option value="0">Select Reason</option>';
                    $content .= '<option value="1">Casual Notes</option>';
                    $content .= '<option value="2">Block Applicant Notes</option>';
                    $content .= '<option value="3">Temporary Not Interested Applicants Notes</option>';
                    $content .= '</select>';
                    $content .= '</div>';
                    $content .= '</div>';
                    $content .= '</div>';
                    $content .= '<div class="modal-footer">';
                    $content .= '<button type="button" class="btn btn-dark" data-dismiss="modal">Close</button>';
                    $content .= '<button type="submit" data-note_key="' . $applicant->id . '" value="cv_sent_save" class="btn btn-teal app_notes_form_submit">Save</button>';
                    $content .= '</div>';
                    $content .= '</form>';
                    $content .= '</div>';
                    $content .= '</div>';
                    $content .= '</div>';
                    return $content;
                }
                return $notes;
            })
            ->editColumn('status', function ($applicant) {
                $statusValue = $this->getApplicantStatus($applicant);
                $colorClass = $statusValue === 'paid' ? 'bg-slate-700' : 'bg-teal-800';
                return '<h3><span class="badge w-100 ' . $colorClass . '">' . strtoupper($statusValue) . '</span></h3>';
            })
            ->editColumn('applicant_job_title', function ($applicant) {
                $jobTitle = JobTitle::where('id', $applicant->applicant_job_title)->first();
                return $jobTitle ? e($jobTitle->name) : '-';
            })
            ->rawColumns(['applicant_postcode', 'applicant_notes', 'status'])
            ->make(true);
    }
    protected function getApplicantsAgainstSales($lat, $lng, $radius, $job_title, $status, $category, $orderColumn, $orderDirection)
    {
        $query = Applicant::query()
            ->select([
                'applicants.*',
                'module_notes.details as module_notes_details',
                'module_notes.created_at as module_notes_created_at',
            ])
            ->where('status', 1)
            ->where('is_no_job', true)
            ->whereNotNull('applicant_lat')
            ->whereNotNull('applicant_lng')
            ->leftJoinSub(
                DB::table('module_notes')
                    ->select('module_notes.*')
                    ->where('module_noteable_type', 'Horsefly\\Applicant')
                    ->whereIn('module_notes.id', function ($query) {
                        $query->select(DB::raw('MAX(id)'))
                            ->from('module_notes')
                            ->where('module_noteable_type', 'Horsefly\\Applicant')
                            ->groupBy('module_noteable_id');
                    }),
                'module_notes',
                fn($join) => $join->on('applicants.id', '=', 'module_notes.module_noteable_id')
            )
            ->whereRaw(
                '(6371 * acos(cos(radians(?)) * cos(radians(applicant_lat)) * cos(radians(applicant_lng) - radians(?)) + sin(radians(?)) * sin(radians(applicant_lat)))) <= ?',
                [$lat, $lng, $lat, $radius]
            )
            ->where('applicant_job_title', $job_title);

        if ($status !== 'all') {
            $query->where('cv_notes_status', $status);
        }

        if ($category) {
            switch ($category) {
                case 'nurse':
                    $query->where('applicant_job_category', 'nurse')
                        ->whereNotIn('applicant_job_title', ['nurse specialist']);
                    break;
                case 'nonnurse':
                    $query->where('applicant_job_category', 'nonnurse')
                        ->whereNotIn('applicant_job_title', ['nonnurse specialist']);
                    break;
                case 'specialist':
                    $query->whereIn('applicant_job_title', ['nurse specialist', 'nonnurse specialist']);
                    break;
                case 'chef':
                    $query->where('applicant_job_category', 'chef');
                    break;
                case 'nursery':
                    $query->where('applicant_job_category', 'nursery');
                    break;
            }
        }

        $applicants = $query->orderBy($orderColumn, $orderDirection)->get()->toArray();
        Log::info('Applicants query executed', [
            'lat' => $lat,
            'lng' => $lng,
            'radius' => $radius,
            'job_title' => $job_title,
            'count' => count($applicants),
        ]);

        return $applicants;
    }
    protected function getApplicantStatus($applicant)
    {
        return $applicant->paid_status === 'close'
            ? 'paid'
            : ($applicant->cv_notes_status === 'active'
                ? 'sent'
                : ($applicant->cv_notes_status === 'disable' ? 'reject' : 'open'));
    }
    public function getResourcesRejectedApplicants(Request $request)
    {
        $typeFilter     = $request->input('type_filter', '');
        $categoryFilter = $request->input('category_filter', '');
        $titleFilter    = $request->input('title_filter', '');
        $searchTerm     = $request->input('search.value', '');
        $dateFilter     = $request->input('date_filter', '');
        $radius         = 15; // in kilometers

        // Cache sales locations for speed (optional: 5 min cache)
        $salesLocations = Cache::remember('active_sales_locations', 300, function () {
            return Sale::select('id', 'job_title_id', 'lat', 'lng', 'sale_postcode')
                ->where('status', 1)
                ->where('is_on_hold', 0)
                ->whereNotNull('lat')
                ->whereNotNull('lng')
                ->get();
        });

        // Latest CRM notes using window function
        $latestNotes = DB::table('crm_notes')
            ->select(
                'id', 'applicant_id', 'sale_id', 'details', 'moved_tab_to',
                'created_at', 'updated_at',
                DB::raw('ROW_NUMBER() OVER (PARTITION BY applicant_id, sale_id ORDER BY id DESC) as row_num')
            );

        // Latest history using window function
        $latestHistory = DB::table('history')
            ->select(
                'id', 'applicant_id', 'sale_id', 'sub_stage', 'status', 'created_at',
                DB::raw('ROW_NUMBER() OVER (PARTITION BY applicant_id, sale_id ORDER BY id DESC) as row_num')
            );

        $model = Applicant::query()
            ->select([
                'crm_notes.details',
                'crm_notes.created_at as crm_notes_created',
                'applicants.id',
                'applicants.applicant_name',
                'applicants.job_title_id',
                'applicants.job_category_id',
                'applicants.applicant_postcode',
                'applicants.applicant_phone',
                'applicants.applicant_experience',
                'applicants.applicant_notes',
                'applicants.paid_status',
                'applicants.applicant_landline',
                'applicants.job_source_id',
                'applicants.status as applicant_status',
                'applicants.created_at as applicant_created',
                'applicants.lat',
                'applicants.lng',
                'applicants.applicant_email',
                'job_titles.name as job_title_name',
                'job_categories.name as job_category_name',
                'job_sources.name as job_source_name',
                DB::raw('
                    CASE 
                        WHEN history.sub_stage = "crm_reject" THEN "Rejected CV" 
                        WHEN history.sub_stage = "crm_request_reject" THEN "Rejected By Request"
                        WHEN history.sub_stage = "crm_interview_not_attended" THEN "Not Attended"
                        WHEN history.sub_stage IN ("crm_start_date_hold", "crm_start_date_hold_save") THEN "Start Date Hold"
                        ELSE "Unknown Status"
                    END AS sub_stage
                ')
            ])
            ->joinSub($latestNotes, 'crm_notes', function ($join) {
                $join->on('applicants.id', '=', 'crm_notes.applicant_id')
                    ->where('crm_notes.row_num', 1);
            })
            ->joinSub($latestHistory, 'history', function ($join) {
                $join->on('applicants.id', '=', 'history.applicant_id')
                    ->on('crm_notes.sale_id', '=', 'history.sale_id')
                    ->where('history.row_num', 1);
            })
            ->leftJoin('job_titles', 'applicants.job_title_id', '=', 'job_titles.id')
            ->leftJoin('job_categories', 'applicants.job_category_id', '=', 'job_categories.id')
            ->leftJoin('job_sources', 'applicants.job_source_id', '=', 'job_sources.id')
            ->whereIn('history.sub_stage', [
                'crm_interview_not_attended',
                'crm_request_reject',
                'crm_reject',
                'crm_start_date_hold',
                'crm_start_date_hold_save'
            ])
            ->whereIn('crm_notes.moved_tab_to', [
                'interview_not_attended',
                'request_reject',
                'cv_sent_reject',
                'start_date_hold',
                'start_date_hold_save'
            ])
            ->where([
                'applicants.status' => 1,
                'history.status'    => 1,
                'applicants.is_in_nurse_home'   => false,
                'applicants.is_blocked'         => false,
                'applicants.is_callback_enable' => false,
                'applicants.is_no_job'          => false,
            ])
            ->with(['jobTitle', 'jobCategory', 'jobSource']);

        // ✅ Distance/Postcode filter
        if ($salesLocations->isNotEmpty()) {
            $postcodes = $salesLocations->pluck('sale_postcode')->filter()->toArray();

            $model->where(function ($query) use ($salesLocations, $radius, $postcodes) {
                foreach ($salesLocations as $sale) {
                    $query->orWhereRaw("
                        (6371 * ACOS(
                            COS(RADIANS(?)) * COS(RADIANS(applicants.lat)) * 
                            COS(RADIANS(applicants.lng) - RADIANS(?)) + 
                            SIN(RADIANS(?)) * SIN(RADIANS(applicants.lat))
                        )) <= ?",
                        [$sale->lat, $sale->lng, $sale->lat, $radius]
                    );
                }

                if (!empty($postcodes)) {
                    $query->orWhereIn('applicants.applicant_postcode', $postcodes);
                }
            });
        }

        // ✅ Date filter
        if ($dateFilter) {
            $now        = Carbon::now();
            $start_date = null;
            $end_date   = $now->copy()->endOfDay();

            switch ($dateFilter) {
                case 'last-3-months':
                    // Last 3 months (up to now)
                    $start_date = $now->copy()->subMonths(3)->startOfDay();
                    break;

                case 'last-6-months':
                    // From 6 months ago up to 3 months ago (skip the most recent 3 months)
                    $end_date   = $now->copy()->subMonths(3)->endOfDay();
                    $start_date = $end_date->copy()->subMonths(6)->startOfDay();
                    break;

                case 'last-9-months':
                    // From 9 months ago up to 6 months ago (skip the most recent 6 months)
                    $end_date   = $now->copy()->subMonths(6)->endOfDay();
                    $start_date = $end_date->copy()->subMonths(9)->startOfDay();
                    break;

                case 'others':
                    // From 5 years ago up to 15 months ago (skip the most recent 15 months)
                    $end_date   = $now->copy()->subMonths(15)->endOfDay();
                    $start_date = $end_date->copy()->subYears(5)->startOfDay();
                    break;
            }

            if ($start_date && $end_date) {
                $model->whereBetween('crm_notes.updated_at', [$start_date, $end_date]);
            }
        }

        // ✅ Sorting
        if ($request->has('order')) {
            $orderColumn   = $request->input('columns.' . $request->input('order.0.column') . '.data');
            $orderDirection = $request->input('order.0.dir', 'asc');

            if ($orderColumn === 'job_source') {
                $model->orderBy('applicants.job_source_id', $orderDirection);
            } elseif ($orderColumn === 'job_category') {
                $model->orderBy('applicants.job_category_id', $orderDirection);
            } elseif ($orderColumn === 'job_title') {
                $model->orderBy('applicants.job_title_id', $orderDirection);
            } elseif ($orderColumn && $orderColumn !== 'DT_RowIndex') {
                $model->orderBy($orderColumn, $orderDirection);
            } else {
                $model->orderBy('crm_notes.updated_at', 'desc');
            }
        } else {
            $model->orderBy('crm_notes.updated_at', 'desc');
        }

        // ✅ Search
        if (!empty($searchTerm)) {
            $model->where(function ($query) use ($searchTerm) {
                $query->where('applicants.applicant_name', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('applicants.applicant_email', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('applicants.applicant_postcode', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('applicants.applicant_phone', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('applicants.applicant_experience', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('applicants.applicant_landline', 'LIKE', "%{$searchTerm}%")
                    ->orWhereHas('jobTitle', fn($q) => $q->where('job_titles.name', 'LIKE', "%{$searchTerm}%"))
                    ->orWhereHas('jobCategory', fn($q) => $q->where('job_categories.name', 'LIKE', "%{$searchTerm}%"))
                    ->orWhereHas('jobSource', fn($q) => $q->where('job_sources.name', 'LIKE', "%{$searchTerm}%"));
            });
        }

        // ✅ Filters
        if ($typeFilter === 'specialist') {
            $model->where('applicants.job_type', 'specialist');
        } elseif ($typeFilter === 'regular') {
            $model->where('applicants.job_type', 'regular');
        }

        if ($categoryFilter) {
            $model->whereIn('applicants.job_category_id', $categoryFilter);
        }

        if ($titleFilter) {
            $model->whereIn('applicants.job_title_id', $titleFilter);
        }

        // ✅ DataTables response
        if ($request->ajax()) {
            return DataTables::eloquent($model)
                ->addIndexColumn()
                ->addColumn('job_title', fn($a) => $a->jobTitle ? strtoupper($a->jobTitle->name) : '-')
                ->addColumn('job_category', function ($a) {
                    $stype = $a->job_type === 'specialist' ? '<br>(Specialist)' : '';
                    return $a->jobCategory ? ucwords($a->jobCategory->name) . $stype : '-';
                })
                ->addColumn('job_source', fn($a) => $a->jobSource ? ucwords($a->jobSource->name) : '-')
                ->addColumn('applicant_name', fn($a) => $a->formatted_applicant_name)
                ->addColumn('applicant_postcode', function ($a) {
                    if ($a->lat && $a->lng) {
                        $url = route('applicants.available_job', ['id' => $a->id, 'radius' => 15]);
                        return '<a href="'. $url .'" style="color:blue;">'. $a->formatted_postcode .'</a>';
                    }
                    return $a->formatted_postcode;
                })
                ->addColumn('applicant_notes', function ($a) {
                    $notes = e($a->details ?? '');
                    $name  = e($a->applicant_name);
                    $postcode = e($a->applicant_postcode);
                    return '<a href="#" title="View Note" onclick="showNotesModal(\''.$a->id.'\', \''.$notes.'\', \''.$name.'\', \''.$postcode.'\')">
                                <iconify-icon icon="solar:eye-scan-bold" class="text-primary fs-24"></iconify-icon>
                            </a>';
                })
                ->addColumn('applicant_phone', fn($a) => $a->formatted_phone)
                ->addColumn('applicant_landline', fn($a) => $a->formatted_landline)
                ->addColumn('applicant_experience', function ($a) {
                    $short = Str::limit(strip_tags($a->applicant_experience), 80);
                    $full  = e($a->applicant_experience);
                    $id    = 'exp-'.$a->id;

                    return '
                        <a href="#" class="text-primary" data-bs-toggle="modal" data-bs-target="#'.$id.'">'.$short.'</a>
                        <div class="modal fade" id="'.$id.'" tabindex="-1" aria-labelledby="'.$id.'-label" aria-hidden="true">
                            <div class="modal-dialog modal-lg modal-dialog-scrollable">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="'.$id.'-label">Applicant Experience</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">'.nl2br($full).'</div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-dark" data-bs-dismiss="modal">Close</button>
                                    </div>
                                </div>
                            </div>
                        </div>';
                })
                ->addColumn('crm_notes_created', fn($a) => Carbon::parse($a->crm_notes_created)->format('d M Y, h:i A'))
                ->addColumn('sub_stage', function ($a) {
                    return match ($a->sub_stage) {
                        'Rejected CV'         => '<span class="badge bg-danger">Rejected CV</span>',
                        'Rejected By Request' => '<span class="badge bg-primary">Rejected By Request</span>',
                        'Not Attended'        => '<span class="badge bg-warning">Not Attended</span>',
                        'Start Date Hold'     => '<span class="badge bg-info">Start Date Hold</span>',
                        default               => '<span class="badge bg-warning">Unknown</span>',
                    };
                })
                ->addColumn('action', function ($a) {
                    $landline = $a->formatted_landline;
                    $phone    = $a->formatted_phone;
                    $postcode = $a->formatted_postcode;
                    $posted   = Carbon::parse($a->applicant_created)->format('d M Y, h:i A');
                    $jobTitle = $a->jobTitle ? strtoupper($a->jobTitle->name) : '-';
                    $jobCat   = $a->jobCategory ? ucwords($a->jobCategory->name) : '-';
                    $jobSrc   = $a->jobSource ? ucwords($a->jobSource->name) : '-';

                    $status = '';
                    if ($a->is_blocked) {
                        $status = '<span class="badge bg-dark">Blocked</span>';
                    } elseif ($a->applicant_status == 1) {
                        $status = '<span class="badge bg-success">Active</span>';
                    } elseif ($a->is_no_response) {
                        $status = '<span class="badge bg-warning">No Response</span>';
                    } elseif ($a->is_circuit_busy) {
                        $status = '<span class="badge bg-warning">Circuit Busy</span>';
                    } elseif ($a->is_no_job) {
                        $status = '<span class="badge bg-warning">No Job</span>';
                    } elseif ($a->applicant_status == 0) {
                        $status = '<span class="badge bg-danger">Inactive</span>';
                    }

                    return '<div class="btn-group dropstart">
                            <button type="button" class="border-0 bg-transparent p-0" data-bs-toggle="dropdown">
                                <iconify-icon icon="solar:menu-dots-square-outline" class="align-middle fs-24 text-dark"></iconify-icon>
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="#" onclick="showDetailsModal(
                                    '.$a->id.',
                                    \''.addslashes(e($a->applicant_name)).'\',
                                    \''.addslashes(e($a->applicant_email)).'\',
                                    \''.addslashes(e($a->applicant_email_secondary ?? '-')).'\',
                                    \''.addslashes(e($postcode)).'\',
                                    \''.addslashes(e($landline)).'\',
                                    \''.addslashes(e($phone)).'\',
                                    \''.addslashes(e($jobTitle)).'\',
                                    \''.addslashes(e($jobCat)).'\',
                                    \''.addslashes(e($jobSrc)).'\',
                                    \''.addslashes(e($posted)).'\',
                                    \''.addslashes(e($status)).'\'
                                )">View</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="#" onclick="viewNotesHistory('.$a->id.')">Notes History</a></li>
                            </ul>
                        </div>';
                })
                ->rawColumns([
                    'applicant_notes', 'applicant_experience', 'applicant_postcode',
                    'applicant_landline', 'applicant_phone', 'job_title', 'sub_stage',
                    'job_category', 'job_source', 'action'
                ])
                ->make(true);
        }

        return response()->json(['error' => 'Invalid request'], 400);
    }
    public function getApplicantHistorybyStatus(Request $request)
    {
        $applicant_id = $request->input('id');
        $status = $request->input('status');

        try{
            $history = CrmNote::join('sales', 'sales.id', '=', 'crm_notes.sale_id')
                ->join('units', 'units.id', '=', 'sales.unit_id')
                ->select(
                    'sales.job_title_id', 
                    'sales.sale_postcode', 
                    'sales.id', 
                    'units.unit_name',
                    'crm_notes.created_at', 
                    'crm_notes.details', 
                    'crm_notes.moved_tab_to',
                    'crm_notes.status',
                )
                ->where('crm_notes.applicant_id', '=', $applicant_id)
                ->latest('created_at')
                ->get();

            if($status == 'rejected'){
                $history->whereIn('crm_notes.moved_tab_to', [
                    'cv_sent_reject', 
                    'request_reject', 
                    'interview_not_attended', 
                    'start_date_hold', 
                    'dispute'
                ]);
            }

            // Return the specific fields you need (e.g., applicant name, notes, etc.)
            return response()->json([
                'data' => $history,
                'success' => true
            ]);
        } catch (\Exception $e) {
            // If an error occurs, catch it and return a meaningful error message
            return response()->json([
                'error' => 'An unexpected error occurred. Please try again later.',
                'message' => $e->getMessage(),
                'success' => false
            ], 500); // Internal server error
        }
    }
    public function getResourcesBlockedApplicants(Request $request)
    {
        $typeFilter = $request->input('type_filter', ''); // Default is empty (no filter)
        $categoryFilter = $request->input('category_filter', ''); // Default is empty (no filter)
        $titleFilter = $request->input('title_filter', ''); // Default is empty (no filter)
        $searchTerm = $request->input('search', ''); // This will get the search query

        $model = Applicant::query()
            ->select([
                'applicants.*',
                'job_titles.name as job_title_name',
                'job_categories.name as job_category_name',
                'job_sources.name as job_source_name'
            ])
            ->where('applicants.is_blocked', true)
            ->where('applicants.status', 1)
            ->leftJoin('job_titles', 'applicants.job_title_id', '=', 'job_titles.id')
            ->leftJoin('job_categories', 'applicants.job_category_id', '=', 'job_categories.id')
            ->leftJoin('job_sources', 'applicants.job_source_id', '=', 'job_sources.id')
            ->leftJoin('applicants_pivot_sales', 'applicants.id', '=', 'applicants_pivot_sales.applicant_id')
            ->with(['jobTitle', 'jobCategory', 'jobSource'])
            ->with(['cv_notes' => function($query) {
                $query->select('status', 'applicant_id', 'sale_id', 'user_id')
                    ->with(['user:id,name'])->latest();
            }])
            ->whereNull('applicants_pivot_sales.applicant_id');
        
        
        // Sorting logic
        if ($request->has('order')) {
            $orderColumn = $request->input('columns.' . $request->input('order.0.column') . '.data');
            $orderDirection = $request->input('order.0.dir', 'asc');

            // Handle special cases first
            if ($orderColumn === 'job_source') {
                $model->orderBy('applicants.job_source_id', $orderDirection);
            } elseif ($orderColumn === 'job_category') {
                $model->orderBy('applicants.job_category_id', $orderDirection);
            } elseif ($orderColumn === 'job_title') {
                $model->orderBy('applicants.job_title_id', $orderDirection);
            }
            // Default case for valid columns
            elseif ($orderColumn && $orderColumn !== 'checkbox') {
                $model->orderBy($orderColumn, $orderDirection);
            }
            // Fallback if no valid order column is found
            else {
                $model->orderBy('applicants.updated_at', 'desc');
            }
        } else {
            // Default sorting when no order is specified
            $model->orderBy('applicants.updated_at', 'desc');
        }

        if ($request->has('search.value')) {
            $searchTerm = (string) $request->input('search.value');

            if (!empty($searchTerm)) {
                $model->where(function ($query) use ($searchTerm) {
                    // Direct column searches
                    $query->where('applicants.applicant_name', 'LIKE', "%{$searchTerm}%")
                        ->orWhere('applicants.applicant_email', 'LIKE', "%{$searchTerm}%")
                        ->orWhere('applicants.applicant_postcode', 'LIKE', "%{$searchTerm}%")
                        ->orWhere('applicants.applicant_phone', 'LIKE', "%{$searchTerm}%")
                        ->orWhere('applicants.applicant_experience', 'LIKE', "%{$searchTerm}%")
                        ->orWhere('applicants.applicant_landline', 'LIKE', "%{$searchTerm}%");

                    // Relationship searches with explicit table names
                    $query->orWhereHas('jobTitle', function ($q) use ($searchTerm) {
                        $q->where('job_titles.name', 'LIKE', "%{$searchTerm}%");
                    });

                    $query->orWhereHas('jobCategory', function ($q) use ($searchTerm) {
                        $q->where('job_categories.name', 'LIKE', "%{$searchTerm}%");
                    });

                    $query->orWhereHas('jobSource', function ($q) use ($searchTerm) {
                        $q->where('job_sources.name', 'LIKE', "%{$searchTerm}%");
                    });
                });
            }
        }

        // Filter by type if it's not empty
        if ($typeFilter == 'specialist') {
            $model->where('applicants.job_type', 'specialist');
        } elseif ($typeFilter == 'regular') {
            $model->where('applicants.job_type', 'regular');
        }

        // Filter by type if it's not empty
        if ($categoryFilter) {
            $model->whereIn('applicants.job_category_id', $categoryFilter);
        }

        // Filter by type if it's not empty
        if ($titleFilter) {
            $model->whereIn('applicants.job_title_id', $titleFilter);
        }

        if ($request->ajax()) {
            return DataTables::eloquent($model)
                ->addColumn('checkbox', function ($applicant) {
                    return '<input type="checkbox" name="applicant_checkbox[]" class="applicant_checkbox" value="' . $applicant->id . '"/>';
                })
                ->addColumn('job_title', function ($applicant) {
                    return $applicant->jobTitle ? strtoupper($applicant->jobTitle->name ): '-';
                })
                ->addColumn('job_category', function ($sale) {
                    $type = $sale->job_type;
                    $stype  = $type && $type == 'specialist' ? '<br>(' . ucwords('Specialist') . ')' : '';
                    return $sale->jobCategory ? ucwords($sale->jobCategory->name) . $stype : '-';
                })
                ->addColumn('job_source', function ($applicant) {
                    return $applicant->jobSource ? ucwords($applicant->jobSource->name) : '-';
                })
                ->addColumn('applicant_name', function ($applicant) {
                    return $applicant->formatted_applicant_name; // Using accessor
                })
                ->addColumn('applicant_postcode', function ($applicant) {
                    $status_value = 'open';
                    if ($applicant->paid_status == 'close') {
                        $status_value = 'paid';
                    } else {
                        foreach ($applicant->cv_notes as $key => $value) {
                            if ($value->status == 'active') {
                                $status_value = 'sent';
                                break;
                            } elseif ($value->status == 'disable') {
                                $status_value = 'reject';
                            }
                        }
                    }

                    if($applicant->lat != null && $applicant->lng != null && $status_value == 'open' || $status_value == 'reject'){
                        $url = route('applicants.available_job', ['id' => $applicant->id, 'radius' => 15]);
                        $button = '<a href="'. $url .'" style="color:blue;">'. $applicant->formatted_postcode .'</a>'; // Using accessor
                    }else{
                        $button = $applicant->formatted_postcode;
                    }
                    return $button;
                })
                ->addColumn('applicant_notes', function ($applicant) {
                    $notes = htmlspecialchars($applicant->applicant_notes, ENT_QUOTES, 'UTF-8');
                    $name = htmlspecialchars($applicant->applicant_name, ENT_QUOTES, 'UTF-8');
                    $postcode = htmlspecialchars($applicant->applicant_postcode, ENT_QUOTES, 'UTF-8');

                    // Tooltip content with additional data-bs-placement and title
                    return '<a href="#" title="View Note" onclick="showNotesModal(\'' . (int)$applicant->id . '\', \'' . $notes . '\', \'' . ucwords($name) . '\', \'' . strtoupper($postcode) . '\')">
                            <iconify-icon icon="solar:eye-scan-bold" class="text-primary fs-24"></iconify-icon>
                        </a>
                        <a href="#" title="Add Short Note" onclick="addShortNotesModal(\'' . (int)$applicant->id . '\')">
                            <iconify-icon icon="solar:clipboard-add-linear" class="text-warning fs-24"></iconify-icon>
                        </a>';
                })
                ->addColumn('applicant_phone', function ($applicant) {
                    return $applicant->formatted_phone; // Using accessor
                })
                ->addColumn('applicant_landline', function ($applicant) {
                    return $applicant->formatted_landline; // Using accessor
                })
                ->addColumn('applicant_experience', function ($applicant) {
                    $short = Str::limit(strip_tags($applicant->applicant_experience), 80);
                    $full = e($applicant->applicant_experience);
                    $id = 'exp-' . $applicant->id;

                    return '
                        <a href="#" class="text-primary" 
                        data-bs-toggle="modal" 
                        data-bs-target="#' . $id . '">
                            ' . $short . '
                        </a>

                        <!-- Modal -->
                        <div class="modal fade" id="' . $id . '" tabindex="-1" aria-labelledby="' . $id . '-label" aria-hidden="true">
                            <div class="modal-dialog modal-lg modal-dialog-scrollable">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="' . $id . '-label">Applicant Experience</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        ' . nl2br($full) . '
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-dark" data-bs-dismiss="modal">Close</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    ';
                })
                ->addColumn('updated_at', function ($applicant) {
                    return $applicant->formatted_updated_at; // Using accessor
                })
                ->addColumn('customStatus', function ($applicant) {
                    $status_value = 'open';
                    $color_class = 'bg-success';
                    if ($applicant->paid_status == 'close') {
                        $status_value = 'paid';
                        $color_class = 'bg-success';
                    } else {
                        foreach ($applicant->cv_notes as $key => $value) {
                            if ($value->status == 'active') {
                                $status_value = 'sent';
                                $color_class = 'bg-success';
                                break;
                            } elseif ($value->status == 'disable') {
                                $status_value = 'reject';
                                $color_class = 'bg-danger';
                            }
                        }
                    }

                    $status = '';
                    $status .= '<span class="badge ' . $color_class . '">';
                    $status .= strtoupper($status_value);
                    $status .= '</span>';
                    return $status;
                })
                ->addColumn('action', function ($applicant) {
                    $landline = $applicant->formatted_landline;
                    $phone = $applicant->formatted_phone;
                    $postcode = $applicant->formatted_postcode;
                    $posted_date = $applicant->formatted_created_at;
                    $job_title = $applicant->jobTitle ? strtoupper($applicant->jobTitle->name) : '-';
                    $job_category = $applicant->jobCategory ? ucwords($applicant->jobCategory->name) : '-';
                    $job_source = $applicant->jobSource ? ucwords($applicant->jobSource->name) : '-';
                    $status = '';

                    if ($applicant->is_blocked) {
                        $status = '<span class="badge bg-dark">Blocked</span>';
                    } elseif ($applicant->status == 1) {
                        $status = '<span class="badge bg-success">Active</span>';
                    } elseif ($applicant->is_no_response) {
                        $status = '<span class="badge bg-danger">No Response</span>';
                    } elseif ($applicant->is_circuit_busy) {
                        $status = '<span class="badge bg-warning">Circuit Busy</span>';
                    } elseif ($applicant->is_no_job) {
                        $status = '<span class="badge bg-secondary">No Job</span>';
                    } elseif ($applicant->status == 0) {
                        $status = '<span class="badge bg-secondary">Inactive</span>';
                    }

                    return '<div class="btn-group dropstart">
                            <button type="button" class="border-0 bg-transparent p-0" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <iconify-icon icon="solar:menu-dots-square-outline" class="align-middle fs-24 text-dark"></iconify-icon>
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="#" onclick="showDetailsModal(
                                    ' . (int)$applicant->id . ',
                                    \'' . addslashes(htmlspecialchars($applicant->applicant_name)) . '\',
                                    \'' . addslashes(htmlspecialchars($applicant->applicant_email)) . '\',
                                    \'' . addslashes(htmlspecialchars($applicant->applicant_email_secondary)) . '\',
                                    \'' . addslashes(htmlspecialchars($postcode)) . '\',
                                    \'' . addslashes(htmlspecialchars($landline)) . '\',
                                    \'' . addslashes(htmlspecialchars($phone)) . '\',
                                    \'' . addslashes(htmlspecialchars($job_title)) . '\',
                                    \'' . addslashes(htmlspecialchars($job_category)) . '\',
                                    \'' . addslashes(htmlspecialchars($job_source)) . '\',
                                    \'' . addslashes(htmlspecialchars($posted_date)) . '\',
                                    \'' . addslashes(htmlspecialchars($status)) . '\'
                                )">View</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="#" onclick="viewNotesHistory(' . $applicant->id . ')">Notes History</a></li>
                            </ul>
                        </div>';
                })
                ->rawColumns(['checkbox', 'applicant_notes', 'applicant_experience', 'applicant_postcode', 'applicant_landline', 'applicant_phone', 'job_title', 'resume', 'customStatus', 'job_category', 'job_source', 'action'])
                ->make(true);
        }
    }
    public function getResourcesPaidApplicants(Request $request)
    {
        $typeFilter = $request->input('type_filter', ''); // Default is empty (no filter)
        $categoryFilter = $request->input('category_filter', ''); // Default is empty (no filter)
        $titleFilter = $request->input('title_filter', ''); // Default is empty (no filter)

        $model = Applicant::query()
            ->select([
                'applicants.*',
                'job_titles.name as job_title_name',
                'job_categories.name as job_category_name',
                'job_sources.name as job_source_name',
                'crm_notes.details',
                'crm_notes.created_at as crm_notes_created',
                'crm_notes.moved_tab_to'
            ])
            ->where('applicants.is_no_job', false)
            ->where('applicants.status', 1)
            ->leftJoin('job_titles', 'applicants.job_title_id', '=', 'job_titles.id')
            ->leftJoin('job_categories', 'applicants.job_category_id', '=', 'job_categories.id')
            ->leftJoin('job_sources', 'applicants.job_source_id', '=', 'job_sources.id')
            ->join('crm_notes', 'applicants.id', '=', 'crm_notes.applicant_id')
            ->with(['jobTitle', 'jobCategory', 'jobSource'])
            ->with(['cv_notes' => function($query) {
                $query->select('status', 'applicant_id', 'sale_id', 'user_id')
                    ->with(['user:id,name'])->latest();
            }])
            ->whereIn('applicants.paid_status', ['open', 'pending'])
            ->whereIn('crm_notes.moved_tab_to', ['paid', 'dispute', 'start_date_hold', 'declined', 'start_date'])
            ->whereIn('crm_notes.id', function ($query) {
                $query->select(DB::raw('MAX(id) FROM crm_notes'))
                    ->whereIn('moved_tab_to', ['paid', 'dispute', 'start_date_hold', 'declined', 'start_date'])
                    ->where('applicants.id', '=', DB::raw('applicant_id'));
            });

        // Sorting logic
        if ($request->has('order')) {
            $orderColumn = $request->input('columns.' . $request->input('order.0.column') . '.data');
            $orderDirection = $request->input('order.0.dir', 'asc');

            // Handle special cases first
            if ($orderColumn === 'job_source') {
                $model->orderBy('applicants.job_source_id', $orderDirection);
            } elseif ($orderColumn === 'job_category') {
                $model->orderBy('applicants.job_category_id', $orderDirection);
            } elseif ($orderColumn === 'job_title') {
                $model->orderBy('applicants.job_title_id', $orderDirection);
            } elseif ($orderColumn === 'customStatus') {
                $model->orderBy('crm_notes.moved_tab_to', $orderDirection);
            }
            // Default case for valid columns
            elseif ($orderColumn && $orderColumn !== 'DT_RowIndex') {
                $model->orderBy($orderColumn, $orderDirection);
            }
            // Fallback if no valid order column is found
            else {
                $model->orderBy('crm_notes.id', 'desc');
            }
        } else {
            // Default sorting when no order is specified
            $model->orderBy('crm_notes.id', 'desc');
        }

        if ($request->has('search.value')) {
            $searchTerm = (string) $request->input('search.value');

            if (!empty($searchTerm)) {
                $model->where(function ($query) use ($searchTerm) {
                    // Direct column searches
                    $query->where('applicants.applicant_name', 'LIKE', "%{$searchTerm}%")
                        ->orWhere('applicants.applicant_email', 'LIKE', "%{$searchTerm}%")
                        ->orWhere('applicants.applicant_postcode', 'LIKE', "%{$searchTerm}%")
                        ->orWhere('applicants.applicant_phone', 'LIKE', "%{$searchTerm}%")
                        ->orWhere('applicants.applicant_experience', 'LIKE', "%{$searchTerm}%")
                        ->orWhere('applicants.applicant_landline', 'LIKE', "%{$searchTerm}%");

                    // Relationship searches with explicit table names
                    $query->orWhereHas('jobTitle', function ($q) use ($searchTerm) {
                        $q->where('job_titles.name', 'LIKE', "%{$searchTerm}%");
                    });

                    $query->orWhereHas('jobCategory', function ($q) use ($searchTerm) {
                        $q->where('job_categories.name', 'LIKE', "%{$searchTerm}%");
                    });

                    $query->orWhereHas('jobSource', function ($q) use ($searchTerm) {
                        $q->where('job_sources.name', 'LIKE', "%{$searchTerm}%");
                    });
                });
            }
        }

        // Filter by type if it's not empty
        if ($typeFilter == 'specialist') {
            $model->where('applicants.job_type', 'specialist');
        } elseif ($typeFilter == 'regular') {
            $model->where('applicants.job_type', 'regular');
        }

        // Filter by type if it's not empty
        if ($categoryFilter) {
            $model->whereIn('applicants.job_category_id', $categoryFilter);
        }

        // Filter by type if it's not empty
        if ($titleFilter) {
            $model->whereIn('applicants.job_title_id', $titleFilter);
        }

        if ($request->ajax()) {
            return DataTables::eloquent($model)
                ->addIndexColumn()
                ->addColumn('job_title', function ($applicant) {
                    return $applicant->jobTitle ? strtoupper($applicant->jobTitle->name) : '-';
                })
                ->addColumn('job_category', function ($sale) {
                    $type = $sale->job_type;
                    $stype  = $type && $type == 'specialist' ? '<br>(' . ucwords('Specialist') . ')' : '';
                    return $sale->jobCategory ? ucwords($sale->jobCategory->name) . $stype : '-';
                })
                ->addColumn('job_source', function ($applicant) {
                    return $applicant->jobSource ? ucwords($applicant->jobSource->name) : '-';
                })
                ->addColumn('applicant_name', function ($applicant) {
                    return $applicant->formatted_applicant_name; // Using accessor
                })
                ->addColumn('applicant_postcode', function ($applicant) {
                    $status_value = 'open';
                    $postcode = '';
                    if ($applicant->paid_status == 'close') {
                        $status_value = 'paid';
                    } else {
                        foreach ($applicant->cv_notes as $key => $value) {
                            if ($value->status == 'active') {
                                $status_value = 'sent';
                                break;
                            } elseif ($value->status == 'disable') {
                                $status_value = 'reject';
                            }
                        }
                    }

                    if($applicant->lat != null && $applicant->lng != null && $status_value == 'open' || $status_value == 'reject'){
                        $url = route('applicants.available_job', ['id' => $applicant->id, 'radius' => 15]);
                        $button = '<a href="'. $url .'" style="color:blue;">'. $applicant->formatted_postcode .'</a>'; // Using accessor
                    }else{
                        $button = $applicant->formatted_postcode;
                    }
                    return $button;
                })
                ->addColumn('applicant_notes', function ($applicant) {
                    $notes = htmlspecialchars($applicant->applicant_notes, ENT_QUOTES, 'UTF-8');
                    $name = htmlspecialchars($applicant->applicant_name, ENT_QUOTES, 'UTF-8');
                    $postcode = htmlspecialchars($applicant->applicant_postcode, ENT_QUOTES, 'UTF-8');

                    // Tooltip content with additional data-bs-placement and title
                    return '<a href="#" title="View Note" onclick="showNotesModal(\'' . $applicant->id . '\', \'' . $notes . '\', \'' . $name . '\', \'' . $postcode . '\')">
                            <iconify-icon icon="solar:eye-scan-bold" class="text-primary fs-24"></iconify-icon>
                        </a>';
                })
                ->addColumn('applicant_phone', function ($applicant) {
                    return $applicant->formatted_phone; // Using accessor
                })
                ->addColumn('applicant_experience', function ($applicant) {
                    $short = Str::limit(strip_tags($applicant->applicant_experience), 80);
                    $full = e($applicant->applicant_experience);
                    $id = 'exp-' . $applicant->id;

                    return '
                        <a href="#" class="text-primary" 
                        data-bs-toggle="modal" 
                        data-bs-target="#' . $id . '">
                            ' . $short . '
                        </a>

                        <!-- Modal -->
                        <div class="modal fade" id="' . $id . '" tabindex="-1" aria-labelledby="' . $id . '-label" aria-hidden="true">
                            <div class="modal-dialog modal-lg modal-dialog-scrollable">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="' . $id . '-label">Applicant Experience</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        ' . nl2br($full) . '
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-dark" data-bs-dismiss="modal">Close</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    ';
                })
                ->addColumn('applicant_landline', function ($applicant) {
                    return $applicant->formatted_landline; // Using accessor
                })
                ->addColumn('crm_notes_created_at', function ($applicant) {
                    return Carbon::parse($applicant->crm_notes_created)->format('d M Y, h:i A'); // Using accessor
                })
                ->addColumn('customStatus', function ($applicant) {
                    $status = '';
                    $statusClr = 'bg-primary';
                    if($applicant->moved_tab_to == 'dispute'){
                        $statusClr = 'bg-warning';
                    }elseif($applicant->moved_tab_to == 'paid'){
                        $statusClr = 'bg-success';
                    }elseif($applicant->moved_tab_to == 'declined'){
                        $statusClr = 'bg-danger';
                    }
                    $status .= '<span class="badge '.$statusClr.'">';
                    $status .= strtoupper($applicant->moved_tab_to);
                    $status .= '</span>';
                    
                    return $status;
                })
                ->addColumn('action', function ($applicant) {
                    $landline = $applicant->formatted_landline;
                    $phone = $applicant->formatted_phone;
                    $postcode = $applicant->formatted_postcode;
                    $posted_date = $applicant->formatted_created_at;
                    $job_title = $applicant->jobTitle ? strtoupper($applicant->jobTitle->name) : '-';
                    $job_category = $applicant->jobCategory ? ucwords($applicant->jobCategory->name) : '-';
                    $job_source = $applicant->jobSource ? ucwords($applicant->jobSource->name) : '-';
                    $status = '';

                    if ($applicant->is_blocked) {
                        $status = '<span class="badge bg-dark">Blocked</span>';
                    } elseif ($applicant->status == 1) {
                        $status = '<span class="badge bg-success">Active</span>';
                    } elseif ($applicant->is_no_response) {
                        $status = '<span class="badge bg-danger">No Response</span>';
                    } elseif ($applicant->is_circuit_busy) {
                        $status = '<span class="badge bg-warning">Circuit Busy</span>';
                    } elseif ($applicant->is_no_job) {
                        $status = '<span class="badge bg-secondary">No Job</span>';
                    } elseif ($applicant->status == 0) {
                        $status = '<span class="badge bg-secondary">Inactive</span>';
                    }

                    return '<div class="btn-group dropstart">
                            <button type="button" class="border-0 bg-transparent p-0" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <iconify-icon icon="solar:menu-dots-square-outline" class="align-middle fs-24 text-dark"></iconify-icon>
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="#" onclick="showDetailsModal(
                                    ' . (int)$applicant->id . ',
                                    \'' . addslashes(htmlspecialchars($applicant->applicant_name)) . '\',
                                    \'' . addslashes(htmlspecialchars($applicant->applicant_email)) . '\',
                                    \'' . addslashes(htmlspecialchars($applicant->applicant_email_secondary)) . '\',
                                    \'' . addslashes(htmlspecialchars($postcode)) . '\',
                                    \'' . addslashes(htmlspecialchars($landline)) . '\',
                                    \'' . addslashes(htmlspecialchars($phone)) . '\',
                                    \'' . addslashes(htmlspecialchars($job_title)) . '\',
                                    \'' . addslashes(htmlspecialchars($job_category)) . '\',
                                    \'' . addslashes(htmlspecialchars($job_source)) . '\',
                                    \'' . addslashes(htmlspecialchars($posted_date)) . '\',
                                    \'' . addslashes(htmlspecialchars($status)) . '\'
                                )">View</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="#" onclick="viewNotesHistory(' . $applicant->id . ')">Notes History</a></li>
                            </ul>
                        </div>';
                })
                ->rawColumns(['applicant_notes', 'applicant_postcode', 'applicant_experience', 'applicant_landline', 'applicant_phone', 'job_title', 'customStatus', 'crm_notes_created_at', 'job_category', 'job_source', 'action'])
                ->make(true);
        }
    }
    public function getResourcesNoJobApplicants(Request $request)
    {
        $typeFilter = $request->input('type_filter', ''); // Default is empty (no filter)
        $categoryFilter = $request->input('category_filter', ''); // Default is empty (no filter)
        $titleFilter = $request->input('title_filter', ''); // Default is empty (no filter)
        $searchTerm = $request->input('search', ''); // This will get the search query

        // Subquery for latest module_notes per applicant
        $latestNotesSub = DB::table('module_notes as mn')
            ->select('mn.id', 'mn.module_noteable_id', 'mn.user_id', 'mn.details', 'mn.created_at')
            ->join(
                DB::raw('(
                    SELECT MAX(id) AS id
                    FROM module_notes
                    WHERE module_noteable_type = "Horsefly\\\\Applicant"
                    GROUP BY module_noteable_id
                ) latest'),
                'latest.id',
                '=',
                'mn.id'
            )
            ->where('mn.module_noteable_type', 'Horsefly\\Applicant');

        $model = Applicant::query()
            ->select([
                'applicants.*',
                'job_titles.name as job_title_name',
                'job_categories.name as job_category_name',
                'job_sources.name as job_source_name',
                'users.name as user_name',
                'module_notes.details as module_notes_details',
                'module_notes.created_at as module_notes_created_at',
            ])
            ->where('applicants.is_no_job', true)
            ->where('applicants.status', 1)
            ->leftJoinSub($latestNotesSub, 'module_notes', function ($join) {
                $join->on('applicants.id', '=', 'module_notes.module_noteable_id');
            })
            ->leftJoin('users', 'module_notes.user_id', '=', 'users.id')
            ->leftJoin('job_titles', 'applicants.job_title_id', '=', 'job_titles.id')
            ->leftJoin('job_categories', 'applicants.job_category_id', '=', 'job_categories.id')
            ->leftJoin('job_sources', 'applicants.job_source_id', '=', 'job_sources.id')
            ->with(['jobTitle', 'jobCategory', 'jobSource'])
            ->distinct();
        
        // Sorting logic
        if ($request->has('order')) {
            $orderColumn = $request->input('columns.' . $request->input('order.0.column') . '.data');
            $orderDirection = $request->input('order.0.dir', 'asc');

            // Handle special cases first
            if ($orderColumn === 'job_source') {
                $model->orderBy('applicants.job_source_id', $orderDirection);
            } elseif ($orderColumn === 'job_category') {
                $model->orderBy('applicants.job_category_id', $orderDirection);
            } elseif ($orderColumn === 'job_title') {
                $model->orderBy('applicants.job_title_id', $orderDirection);
            }
            // Default case for valid columns
            elseif ($orderColumn && $orderColumn !== 'checkbox') {
                $model->orderBy($orderColumn, $orderDirection);
            }
            // Fallback if no valid order column is found
            else {
                $model->orderBy('applicants.updated_at', 'desc');
            }
        } else {
            // Default sorting when no order is specified
            $model->orderBy('applicants.updated_at', 'desc');
        }

        if ($request->has('search.value')) {
            $searchTerm = (string) $request->input('search.value');

            if (!empty($searchTerm)) {
                $model->where(function ($query) use ($searchTerm) {
                    // Direct column searches
                    $query->where('applicants.applicant_name', 'LIKE', "%{$searchTerm}%")
                        ->orWhere('applicants.applicant_email', 'LIKE', "%{$searchTerm}%")
                        ->orWhere('applicants.applicant_postcode', 'LIKE', "%{$searchTerm}%")
                        ->orWhere('applicants.applicant_phone', 'LIKE', "%{$searchTerm}%")
                        ->orWhere('applicants.applicant_experience', 'LIKE', "%{$searchTerm}%")
                        ->orWhere('applicants.applicant_landline', 'LIKE', "%{$searchTerm}%");

                    // Relationship searches with explicit table names
                    $query->orWhereHas('jobTitle', function ($q) use ($searchTerm) {
                        $q->where('job_titles.name', 'LIKE', "%{$searchTerm}%");
                    });

                    $query->orWhereHas('jobCategory', function ($q) use ($searchTerm) {
                        $q->where('job_categories.name', 'LIKE', "%{$searchTerm}%");
                    });

                    $query->orWhereHas('jobSource', function ($q) use ($searchTerm) {
                        $q->where('job_sources.name', 'LIKE', "%{$searchTerm}%");
                    });

                    $query->orWhereHas('user', function ($q) use ($searchTerm) {
                        $q->where('users.name', 'LIKE', "%{$searchTerm}%");
                    });
                });
            }
        }

        // Filter by type if it's not empty
        if ($typeFilter == 'specialist') {
            $model->where('applicants.job_type', 'specialist');
        } elseif ($typeFilter == 'regular') {
            $model->where('applicants.job_type', 'regular');
        }

        // Filter by type if it's not empty
        if ($categoryFilter) {
            $model->whereIn('applicants.job_category_id', $categoryFilter);
        }

        // Filter by type if it's not empty
        if ($titleFilter) {
            $model->whereIn('applicants.job_title_id', $titleFilter);
        }

        if ($request->ajax()) {
            return DataTables::eloquent($model)
                ->addColumn('checkbox', function ($applicant) {
                    return '<input type="checkbox" name="applicant_checkbox[]" class="applicant_checkbox" value="' . $applicant->id . '"/>';
                })
                ->addColumn('job_title', function ($applicant) {
                    return $applicant->jobTitle ? strtoupper($applicant->jobTitle->name) : '-';
                })
                ->addColumn('job_category', function ($sale) {
                    $type = $sale->job_type;
                    $stype  = $type && $type == 'specialist' ? '<br>(' . ucwords('Specialist') . ')' : '';
                    return $sale->jobCategory ? ucwords($sale->jobCategory->name) . $stype : '-';
                })
                ->addColumn('job_source', function ($applicant) {
                    return $applicant->jobSource ? ucwords($applicant->jobSource->name) : '-';
                })
                ->addColumn('user_name', function ($applicant) {
                    return $applicant->user_name ? ucwords($applicant->user_name) : '-';
                })
                ->addColumn('applicant_name', function ($applicant) {
                    return $applicant->formatted_applicant_name; // Using accessor
                })
                ->addColumn('applicant_postcode', function ($applicant) {
                    $status_value = 'open';
                    if ($applicant->paid_status == 'close') {
                        $status_value = 'paid';
                    } else {
                        foreach ($applicant->cv_notes as $key => $value) {
                            if ($value->status == 'active') {
                                $status_value = 'sent';
                                break;
                            } elseif ($value->status == 'disable') {
                                $status_value = 'reject';
                            }
                        }
                    }

                    if($applicant->lat != null && $applicant->lng != null && $status_value == 'open' || $status_value == 'reject'){
                        $url = route('applicants.available_no_job', ['id' => (int)$applicant->id, 'radius' => 15]);
                        $button = '<a href="'. $url .'" style="color:blue;" target="_blank">'. $applicant->formatted_postcode .'</a>'; // Using accessor
                    }else{
                        $button = $applicant->formatted_postcode;
                    }
                    return $button;
                })
                ->addColumn('applicant_notes', function ($applicant) {
                    $notes = htmlspecialchars($applicant->applicant_notes, ENT_QUOTES, 'UTF-8');
                    $name = htmlspecialchars($applicant->applicant_name, ENT_QUOTES, 'UTF-8');
                    $postcode = htmlspecialchars($applicant->applicant_postcode, ENT_QUOTES, 'UTF-8');

                    // Tooltip content with additional data-bs-placement and title
                    return '<a href="#" title="View Note" onclick="showNotesModal(\'' . (int)$applicant->id . '\', \'' . $notes . '\', \'' . ucwords($name) . '\', \'' . strtoupper($postcode) . '\')">
                            <iconify-icon icon="solar:eye-scan-bold" class="text-primary fs-24"></iconify-icon>
                        </a>
                        <a href="#" title="Add Short Note" onclick="addShortNotesModal(\'' . (int)$applicant->id . '\')">
                            <iconify-icon icon="solar:clipboard-add-linear" class="text-warning fs-24"></iconify-icon>
                        </a>';
                })
                ->addColumn('applicant_phone', function ($applicant) {
                    $strng = '';
                    if($applicant->applicant_landline){
                        $phone = '<strong>P:</strong> '.$applicant->applicant_phone;
                        $landline = '<strong>L:</strong> '.$applicant->applicant_landline;

                        $strng = $applicant->is_blocked ? "<span class='badge bg-dark'>Blocked</span>" : $phone .'<br>'. $landline;
                    }else{
                        $phone = '<strong>P:</strong> '.$applicant->applicant_phone;
                        $strng = $applicant->is_blocked ? "<span class='badge bg-dark'>Blocked</span>" : $phone;
                    }

                    return $strng;
                })
                ->addColumn('applicant_resume', function ($applicant) {
                    $filePath = $applicant->applicant_cv;
                    $fileExists = $applicant->applicant_cv && Storage::disk('public')->exists($filePath);

                    if (!$applicant->is_blocked && $fileExists) {
                        return '<a href="' . asset('storage/' . $filePath) . '" title="Download CV" target="_blank" class="text-decoration-none">' .
                            '<iconify-icon icon="solar:download-square-bold" class="text-success fs-28"></iconify-icon></a>';
                    }

                    return '<button disabled title="CV Not Available" class="border-0 bg-transparent p-0">' .
                        '<iconify-icon icon="solar:download-square-bold" class="text-grey fs-28"></iconify-icon></button>';
                })
                ->addColumn('crm_resume', function ($applicant) {
                    $filePath = $applicant->updated_cv;
                    $fileExists = $applicant->updated_cv && Storage::disk('public')->exists($filePath);

                    if (!$applicant->is_blocked && $fileExists) {
                        return '<a href="' . asset('storage/' . $filePath) . '" title="Download Updated CV" target="_blank" class="text-decoration-none">' .
                            '<iconify-icon icon="solar:download-square-bold" class="text-primary fs-28"></iconify-icon></a>';
                    }

                    return '<button disabled title="CV Not Available" class="border-0 bg-transparent p-0">' .
                        '<iconify-icon icon="solar:download-square-bold" class="text-grey fs-28"></iconify-icon></button>';
                })
                ->addColumn('applicant_experience', function ($applicant) {
                    $short = Str::limit(strip_tags($applicant->applicant_experience), 80);
                    $full = e($applicant->applicant_experience);
                    $id = 'exp-' . $applicant->id;

                    return '
                        <a href="#" class="text-primary" 
                        data-bs-toggle="modal" 
                        data-bs-target="#' . $id . '">
                            ' . $short . '
                        </a>

                        <!-- Modal -->
                        <div class="modal fade" id="' . $id . '" tabindex="-1" aria-labelledby="' . $id . '-label" aria-hidden="true">
                            <div class="modal-dialog modal-lg modal-dialog-scrollable">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="' . $id . '-label">Applicant Experience</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        ' . nl2br($full) . '
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-dark" data-bs-dismiss="modal">Close</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    ';
                })
                ->addColumn('updated_at', function ($applicant) {
                    return $applicant->formatted_updated_at; // Using accessor
                })
                ->addColumn('customStatus', function ($applicant) {
                    $status_value = 'open';
                    $color_class = 'bg-primary';
                    if ($applicant->paid_status == 'close') {
                        $status_value = 'paid';
                        $color_class = 'bg-info';
                    } else {
                        foreach ($applicant->cv_notes as $key => $value) {
                            if ($value->status == 'active') {
                                $status_value = 'sent';
                                $color_class = 'bg-success';
                                break;
                            } elseif ($value->status == 'disable') {
                                $status_value = 'reject';
                                $color_class = 'bg-danger';
                            }
                        }
                    }

                    $status = '';
                    $status .= '<span class="badge ' . $color_class . '">';
                    $status .= strtoupper($status_value);
                    $status .= '</span>';
                    return $status;
                })
                ->addColumn('action', function ($applicant) {
                    $landline = $applicant->formatted_landline;
                    $phone = $applicant->formatted_phone;
                    $postcode = $applicant->formatted_postcode;
                    $posted_date = $applicant->formatted_created_at;
                    $job_title = $applicant->jobTitle ? strtoupper($applicant->jobTitle->name) : '-';
                    $job_category = $applicant->jobCategory ? ucwords($applicant->jobCategory->name) : '-';
                    $job_source = $applicant->jobSource ? ucwords($applicant->jobSource->name) : '-';
                    $status = '';

                    if ($applicant->is_blocked) {
                        $status = '<span class="badge bg-dark">Blocked</span>';
                    } elseif ($applicant->status) {
                        $status = '<span class="badge bg-success">Active</span>';
                    } elseif ($applicant->is_no_response) {
                        $status = '<span class="badge bg-danger">No Response</span>';
                    } elseif ($applicant->is_circuit_busy) {
                        $status = '<span class="badge bg-warning">Circuit Busy</span>';
                    } elseif ($applicant->is_no_job) {
                        $status = '<span class="badge bg-secondary">No Job</span>';
                    } else {
                        $status = '<span class="badge bg-secondary">Inactive</span>';
                    }

                    return '<div class="btn-group dropstart">
                            <button type="button" class="border-0 bg-transparent p-0" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <iconify-icon icon="solar:menu-dots-square-outline" class="align-middle fs-24 text-dark"></iconify-icon>
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="#" onclick="showDetailsModal(
                                    ' . (int)$applicant->id . ',
                                    \'' . addslashes(htmlspecialchars($applicant->applicant_name)) . '\',
                                    \'' . addslashes(htmlspecialchars($applicant->applicant_email)) . '\',
                                    \'' . addslashes(htmlspecialchars($applicant->applicant_email_secondary)) . '\',
                                    \'' . addslashes(htmlspecialchars($postcode)) . '\',
                                    \'' . addslashes(htmlspecialchars($landline)) . '\',
                                    \'' . addslashes(htmlspecialchars($phone)) . '\',
                                    \'' . addslashes(htmlspecialchars($job_title)) . '\',
                                    \'' . addslashes(htmlspecialchars($job_category)) . '\',
                                    \'' . addslashes(htmlspecialchars($job_source)) . '\',
                                    \'' . addslashes(htmlspecialchars($posted_date)) . '\',
                                    \'' . addslashes(htmlspecialchars($status)) . '\'
                                )">View</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="#" onclick="viewNotesHistory(' . $applicant->id . ')">Notes History</a></li>
                            </ul>
                        </div>';
                })
                ->rawColumns(['checkbox', 'applicant_resume', 'crm_resume', 'user_name', 'applicant_experience', 'applicant_notes', 'applicant_postcode', 'applicant_phone', 'job_title', 'resume', 'customStatus', 'job_category', 'job_source', 'action'])
                ->make(true);
        }
    }
    public function getResourcesNotInterestedApplicants(Request $request)
    {
        $typeFilter = $request->input('type_filter', ''); // Default is empty (no filter)
        $categoryFilter = $request->input('category_filter', ''); // Default is empty (no filter)
        $titleFilter = $request->input('title_filter', ''); // Default is empty (no filter)

        $model = Applicant::query()
            ->select([
                'applicants.*',
                'job_titles.name as job_title_name',
                'job_categories.name as job_category_name',
                'job_sources.name as job_source_name',
                // Offices
                'offices.office_name',
                // Sales
                'sales.id as sale_id',
                'sales.job_category_id as sale_category_id',
                'sales.job_title_id as sale_title_id',
                'sales.sale_postcode',
                'sales.job_type as sale_job_type',
                'sales.timing',
                'sales.salary',
                'sales.experience as sale_experience',
                'sales.qualification as sale_qualification',
                'sales.benefits',
                'sales.office_id as sale_office_id',
                'sales.unit_id as sale_unit_id',
                'sales.position_type',
                'sales.status as sale_status',
                'sales.created_at as sale_posted_date',
                // Units
                'units.unit_name',
                'units.unit_postcode',
                'units.unit_website',
                // Users
                'users.name as user_name',
                'notes_for_range_applicants.reason',
                'applicants_pivot_sales.created_at as pivot_created_at',
            ])
            ->where('applicants.is_no_job', false)
            ->where('applicants.is_temp_not_interested', true)
            ->where('applicants.status', 1)
            ->join('applicants_pivot_sales', 'applicants_pivot_sales.applicant_id', '=', 'applicants.id')
            ->join('notes_for_range_applicants', 'applicants_pivot_sales.id', '=', 'notes_for_range_applicants.applicants_pivot_sales_id')
            ->join('sales', 'sales.id', '=', 'applicants_pivot_sales.sale_id')
            ->join('module_notes', 'applicants.id', '=', 'module_notes.module_noteable_id')
            ->leftJoin('users', 'module_notes.user_id', '=', 'users.id')
            ->leftJoin('job_titles', 'applicants.job_title_id', '=', 'job_titles.id')
            ->leftJoin('job_categories', 'applicants.job_category_id', '=', 'job_categories.id')
            ->leftJoin('job_sources', 'applicants.job_source_id', '=', 'job_sources.id')
            ->join('offices', 'offices.id', '=', 'sales.office_id')
            ->join('units', 'units.id', '=', 'sales.unit_id')
            ->with(['jobTitle', 'jobCategory', 'jobSource'])
            ->distinct();
        
        // Sorting logic
        if ($request->has('order')) {
            $orderColumn = $request->input('columns.' . $request->input('order.0.column') . '.data');
            $orderDirection = $request->input('order.0.dir', 'asc');

            // Handle special cases first
            if ($orderColumn === 'job_source') {
                $model->orderBy('applicants.job_source_id', $orderDirection);
            } elseif ($orderColumn === 'job_category') {
                $model->orderBy('applicants.job_category_id', $orderDirection);
            } elseif ($orderColumn === 'job_title') {
                $model->orderBy('applicants.job_title_id', $orderDirection);
            }
            // Default case for valid columns
            elseif ($orderColumn && $orderColumn !== 'checkbox') {
                $model->orderBy($orderColumn, $orderDirection);
            }
            // Fallback if no valid order column is found
            else {
                $model->orderBy('applicants.updated_at', 'desc');
            }
        } else {
            // Default sorting when no order is specified
            $model->orderBy('applicants.updated_at', 'desc');
        }

        if ($request->has('search.value')) {
            $searchTerm = (string) $request->input('search.value');

            if (!empty($searchTerm)) {
                $model->where(function ($query) use ($searchTerm) {
                    // Direct column searches
                    $query->where('applicants.applicant_name', 'LIKE', "%{$searchTerm}%")
                        ->orWhere('applicants.applicant_email', 'LIKE', "%{$searchTerm}%")
                        ->orWhere('applicants.applicant_postcode', 'LIKE', "%{$searchTerm}%")
                        ->orWhere('applicants.applicant_phone', 'LIKE', "%{$searchTerm}%")
                        ->orWhere('applicants.applicant_experience', 'LIKE', "%{$searchTerm}%")
                        ->orWhere('applicants.applicant_landline', 'LIKE', "%{$searchTerm}%");

                    // Relationship searches with explicit table names
                    $query->orWhereHas('jobTitle', function ($q) use ($searchTerm) {
                        $q->where('job_titles.name', 'LIKE', "%{$searchTerm}%");
                    });

                    $query->orWhereHas('jobCategory', function ($q) use ($searchTerm) {
                        $q->where('job_categories.name', 'LIKE', "%{$searchTerm}%");
                    });

                    $query->orWhereHas('jobSource', function ($q) use ($searchTerm) {
                        $q->where('job_sources.name', 'LIKE', "%{$searchTerm}%");
                    });

                    $query->orWhereHas('user', function ($q) use ($searchTerm) {
                        $q->where('users.name', 'LIKE', "%{$searchTerm}%");
                    });
                });
            }
        }

        // Filter by type if it's not empty
        if ($typeFilter == 'specialist') {
            $model->where('applicants.job_type', 'specialist');
        } elseif ($typeFilter == 'regular') {
            $model->where('applicants.job_type', 'regular');
        }

        // Filter by type if it's not empty
        if ($categoryFilter) {
            $model->whereIn('applicants.job_category_id', $categoryFilter);
        }

        // Filter by type if it's not empty
        if ($titleFilter) {
            $model->whereIn('applicants.job_title_id', $titleFilter);
        }

        if ($request->ajax()) {
            return DataTables::eloquent($model)
                ->addColumn('checkbox', function ($applicant) {
                    return '<input type="checkbox" name="applicant_checkbox[]" class="applicant_checkbox" value="' . $applicant->id . '"/>';
                })
                ->addColumn('job_title', function ($applicant) {
                    return $applicant->jobTitle ? strtoupper($applicant->jobTitle->name) : '-';
                })
                ->addColumn('job_category', function ($sale) {
                    $type = $sale->job_type;
                    $stype  = $type && $type == 'specialist' ? '<br>(' . ucwords('Specialist') . ')' : '';
                    return $sale->jobCategory ? ucwords($sale->jobCategory->name) . $stype : '-';
                })
                ->addColumn('job_source', function ($applicant) {
                    return $applicant->jobSource ? ucwords($applicant->jobSource->name) : '-';
                })
                ->addColumn('job_details', function ($applicant) {
                    $position_type = strtoupper(str_replace('-', ' ', $applicant->position_type));
                    $position = '<span class="badge bg-primary">' . htmlspecialchars($position_type, ENT_QUOTES) . '</span>';

                    if ($applicant->sale_status == 1) {
                        $status = '<span class="badge bg-success">Active</span>';
                    } elseif ($applicant->sale_status == 0 && $applicant->is_on_hold == 0) {
                        $status = '<span class="badge bg-danger">Closed</span>';
                    } elseif ($applicant->sale_status == 2) {
                        $status = '<span class="badge bg-warning">Pending</span>';
                    } elseif ($applicant->sale_status == 3) {
                        $status = '<span class="badge bg-danger">Rejected</span>';
                    }

                    // Escape HTML in $status for JavaScript (to prevent XSS)
                    $escapedStatus = htmlspecialchars($status, ENT_QUOTES);

                    // Prepare modal HTML for the "Job Details"
                    $modalHtml = $this->generateJobDetailsModal($applicant);

                    // Return the action link with a modal trigger and the modal HTML
                    return '<a href="#" class="dropdown-item" style="color: blue;" onclick="showJobDetailsModal('
                        . (int)$applicant->sale_id . ','
                        . '\'' . htmlspecialchars(Carbon::parse($applicant->sale_posted_date)->format('d M Y, h:i A'), ENT_QUOTES) . '\','
                        . '\'' . htmlspecialchars((string)$applicant->office_name, ENT_QUOTES) . '\','
                        . '\'' . htmlspecialchars((string)$applicant->unit_name, ENT_QUOTES) . '\','
                        . '\'' . htmlspecialchars((string)$applicant->sale_postcode, ENT_QUOTES) . '\','
                        . '\'' . htmlspecialchars((string)$applicant->jobCategory->name, ENT_QUOTES) . '\','
                        . '\'' . htmlspecialchars((string)$applicant->jobTitle->name, ENT_QUOTES) . '\','
                        . '\'' . $escapedStatus . '\','
                        . '\'' . htmlspecialchars((string)$applicant->timing, ENT_QUOTES) . '\','
                        . '\'' . htmlspecialchars((string)$applicant->sale_experience, ENT_QUOTES) . '\','
                        . '\'' . htmlspecialchars((string)$applicant->salary, ENT_QUOTES) . '\','
                        . '\'' . htmlspecialchars((string)$position, ENT_QUOTES) . '\','
                        . '\'' . htmlspecialchars((string)$applicant->sale_qualification, ENT_QUOTES) . '\','
                        . '\'' . htmlspecialchars((string)$applicant->benefits, ENT_QUOTES) . '\')">
                        <iconify-icon icon="solar:square-arrow-right-up-bold" class="text-info fs-24"></iconify-icon>
                        </a>' . $modalHtml;
                })
                ->addColumn('user_name', function ($applicant) {
                    return $applicant->user_name ? ucwords($applicant->user_name) : '-';
                })
                ->addColumn('applicant_name', function ($applicant) {
                    return $applicant->formatted_applicant_name; // Using accessor
                })
                ->addColumn('applicant_postcode', function ($applicant) {
                    $status_value = 'open';
                    if ($applicant->paid_status == 'close') {
                        $status_value = 'paid';
                    } else {
                        foreach ($applicant->cv_notes as $key => $value) {
                            if ($value->status == 'active') {
                                $status_value = 'sent';
                                break;
                            } elseif ($value->status == 'disable') {
                                $status_value = 'reject';
                            }
                        }
                    }

                    if($applicant->lat != null && $applicant->lng != null && $status_value == 'open' || $status_value == 'reject'){
                        $url = route('applicants.available_no_job', ['id' => (int)$applicant->id, 'radius' => 15]);
                        $button = '<a href="'. $url .'" style="color:blue;" target="_blank">'. $applicant->formatted_postcode .'</a>'; // Using accessor
                    }else{
                        $button = $applicant->formatted_postcode;
                    }
                    return $button;
                })
                ->addColumn('notes_detail', function ($applicant) {
                    $notes_detail = strip_tags($applicant->reason); // avoid double escaping
                    $notes_created_at = Carbon::parse($applicant->pivot_created_at)->format('d M Y, h:i A');
                    $notes = "<strong>Date: {$notes_created_at}</strong><br>{$notes_detail}";

                    $short = Str::limit($notes, 80);
                    $modalId = 'crm-' . $applicant->id;

                    $name = e($applicant->applicant_name);
                    $postcode = e($applicant->applicant_postcode);
                    $notesEscaped = nl2br(e($notes_detail));

                    return '
                        <a href="#" class="text-primary" 
                        data-bs-toggle="modal" 
                        data-bs-target="#' . $modalId . '">
                            ' . $short . '
                        </a>

                        <!-- Modal -->
                        <div class="modal fade" id="' . $modalId . '" tabindex="-1" aria-labelledby="' . $modalId . '-label" aria-hidden="true">
                            <div class="modal-dialog modal-lg modal-dialog-scrollable">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="' . $modalId . '-label">Applicant\'s Notes</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body modal-body-text-left">
                                        <p><strong>Name:</strong> ' . $name . '</p>
                                        <p><strong>Postcode:</strong> ' . $postcode . '</p>
                                        <p><strong>Date:</strong> ' . $notes_created_at . '</p>
                                        <p class="notes-content"><strong>Notes Detail:</strong><br>' . $notesEscaped . '</p>
                                    </div>
                                     <div class="modal-footer">
                                        <button type="button" class="btn btn-dark" data-bs-dismiss="modal">Close</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    ';
                })
                ->addColumn('applicant_phone', function ($applicant) {
                    $strng = '';
                    if($applicant->applicant_landline){
                        $phone = '<strong>P:</strong> '.$applicant->applicant_phone;
                        $landline = '<strong>L:</strong> '.$applicant->applicant_landline;

                        $strng = $applicant->is_blocked ? "<span class='badge bg-dark'>Blocked</span>" : $phone .'<br>'. $landline;
                    }else{
                        $phone = '<strong>P:</strong> '.$applicant->applicant_phone;
                        $strng = $applicant->is_blocked ? "<span class='badge bg-dark'>Blocked</span>" : $phone;
                    }

                    return $strng;
                })
                ->addColumn('applicant_experience', function ($applicant) {
                    $short = Str::limit(strip_tags($applicant->applicant_experience), 80);
                    $full = e($applicant->applicant_experience);
                    $id = 'exp-' . $applicant->id;

                    return '
                        <a href="#" class="text-primary" 
                        data-bs-toggle="modal" 
                        data-bs-target="#' . $id . '">
                            ' . $short . '
                        </a>

                        <!-- Modal -->
                        <div class="modal fade" id="' . $id . '" tabindex="-1" aria-labelledby="' . $id . '-label" aria-hidden="true">
                            <div class="modal-dialog modal-lg modal-dialog-scrollable">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="' . $id . '-label">Applicant Experience</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        ' . nl2br($full) . '
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-dark" data-bs-dismiss="modal">Close</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    ';
                })
                ->addColumn('pivot_created_at', function ($applicant) {
                    return Carbon::parse($applicant->pivot_created_at)->format('d M Y, h:i A'); // Using accessor
                })
                ->addColumn('customStatus', function ($applicant) {
                    $status_value = 'open';
                    $color_class = 'bg-primary';
                    if ($applicant->paid_status == 'close') {
                        $status_value = 'paid';
                        $color_class = 'bg-info';
                    } else {
                        foreach ($applicant->cv_notes as $key => $value) {
                            if ($value->status == 'active') {
                                $status_value = 'sent';
                                $color_class = 'bg-success';
                                break;
                            } elseif ($value->status == 'disable') {
                                $status_value = 'reject';
                                $color_class = 'bg-danger';
                            }
                        }
                    }

                    $status = '';
                    $status .= '<span class="badge ' . $color_class . '">';
                    $status .= strtoupper($status_value);
                    $status .= '</span>';
                    return $status;
                })
                ->addColumn('action', function ($applicant) {
                    $landline = $applicant->formatted_landline;
                    $phone = $applicant->formatted_phone;
                    $postcode = $applicant->formatted_postcode;
                    $posted_date = $applicant->formatted_created_at;
                    $job_title = $applicant->jobTitle ? strtoupper($applicant->jobTitle->name) : '-';
                    $job_category = $applicant->jobCategory ? ucwords($applicant->jobCategory->name) : '-';
                    $job_source = $applicant->jobSource ? ucwords($applicant->jobSource->name) : '-';
                    $status = '';

                    if ($applicant->is_blocked) {
                        $status = '<span class="badge bg-dark">Blocked</span>';
                    } elseif ($applicant->status) {
                        $status = '<span class="badge bg-success">Active</span>';
                    } elseif ($applicant->is_no_response) {
                        $status = '<span class="badge bg-danger">No Response</span>';
                    } elseif ($applicant->is_circuit_busy) {
                        $status = '<span class="badge bg-warning">Circuit Busy</span>';
                    } elseif ($applicant->is_no_job) {
                        $status = '<span class="badge bg-secondary">No Job</span>';
                    } else {
                        $status = '<span class="badge bg-secondary">Inactive</span>';
                    }

                    return '<div class="btn-group dropstart">
                            <button type="button" class="border-0 bg-transparent p-0" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <iconify-icon icon="solar:menu-dots-square-outline" class="align-middle fs-24 text-dark"></iconify-icon>
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="#" onclick="showDetailsModal(
                                    ' . (int)$applicant->id . ',
                                    \'' . addslashes(htmlspecialchars($applicant->applicant_name)) . '\',
                                    \'' . addslashes(htmlspecialchars($applicant->applicant_email)) . '\',
                                    \'' . addslashes(htmlspecialchars($applicant->applicant_email_secondary)) . '\',
                                    \'' . addslashes(htmlspecialchars($postcode)) . '\',
                                    \'' . addslashes(htmlspecialchars($landline)) . '\',
                                    \'' . addslashes(htmlspecialchars($phone)) . '\',
                                    \'' . addslashes(htmlspecialchars($job_title)) . '\',
                                    \'' . addslashes(htmlspecialchars($job_category)) . '\',
                                    \'' . addslashes(htmlspecialchars($job_source)) . '\',
                                    \'' . addslashes(htmlspecialchars($posted_date)) . '\',
                                    \'' . addslashes(htmlspecialchars($status)) . '\'
                                )">View</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="#" onclick="viewNotesHistory(' . $applicant->id . ')">Notes History</a></li>
                            </ul>
                        </div>';
                })
                ->rawColumns(['checkbox', 'user_name', 'applicant_experience', 'job_details', 'notes_detail', 'applicant_postcode', 'applicant_phone', 'job_title', 'customStatus', 'job_category', 'job_source', 'action'])
                ->make(true);
        }
    }
    private function generateJobDetailsModal($applicant)
    {
        $modalId = 'jobDetailsModal_' . $applicant->sale_id;  // Unique modal ID for each applicant's job details

        return '<div class="modal fade" id="' . $modalId . '" tabindex="-1" aria-labelledby="' . $modalId . 'Label" aria-hidden="true">
                    <div class="modal-dialog modal-lg modal-dialog-top modal-dialog-scrollable">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="' . $modalId . 'Label">Job Details</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body modal-body-text-left">
                                <!-- Job details content will be dynamically inserted here -->
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-dark" data-bs-dismiss="modal">Close</button>
                            </div>
                        </div>
                    </div>
                </div>';
    }
    public function getResourcesCategoryWised(Request $request)
    {
        $typeFilter = $request->input('type_filter', ''); // Default is empty (no filter)
        $categoryFilter = $request->input('category_filter', ''); // Default is empty (no filter)
        $titleFilter = $request->input('title_filter', ''); // Default is empty (no filter)
        $dateRangeFilter = $request->input('date_range_filter', ''); // Default is empty (no filter)
        $statusFilter = $request->input('status_filter', ''); // Default is empty (no filter)

        $latestCvNotesSub = DB::table('cv_notes as c1')
            ->select('c1.id')
            ->whereRaw('c1.id = (
                SELECT MAX(c2.id)
                FROM cv_notes c2
                WHERE c2.applicant_id = c1.applicant_id
                AND c2.sale_id = c1.sale_id
            )');

        $cvNotesSubQuery = DB::table('cv_notes')
            ->joinSub($latestCvNotesSub, 'latest', function ($join) {
                $join->on('cv_notes.id', '=', 'latest.id');
            })
            ->select(
                'cv_notes.applicant_id',
                'cv_notes.sale_id',
                'cv_notes.user_id',
                'cv_notes.status'
            );

        $model = Applicant::query()
            ->select([
                'applicants.id',
                'applicants.applicant_name',
                'applicants.job_category_id',
                'applicants.job_title_id',
                'applicants.job_source_id',
                'applicants.applicant_postcode',
                'applicants.applicant_email',
                'applicants.applicant_email_secondary',
                'applicants.applicant_phone',
                'applicants.status',
                'applicants.applicant_landline',
                'applicants.created_at',
                'applicants.updated_at',
                'applicants.is_job_within_radius',
                'applicants.applicant_notes',
                'applicants.applicant_experience',
                'applicants.applicant_cv',
                'applicants.updated_cv',

                'job_titles.name as job_title_name',
                'job_categories.name as job_category_name',
                'job_sources.name as job_source_name',
                'applicants_pivot_sales.sale_id as pivot_sale_id',
                'applicants_pivot_sales.id as pivot_id',
                'users.name as user_name',
                'cv_notes.status as cv_note_status',
                'latest_module_note.latest_note_created',
            ])
            ->where('applicants.status', 1)

            // Pivot & notes
            ->leftJoin('applicants_pivot_sales', 'applicants.id', '=', 'applicants_pivot_sales.applicant_id')
            ->leftJoin('notes_for_range_applicants', 'applicants_pivot_sales.id', '=', 'notes_for_range_applicants.applicants_pivot_sales_id')

            // Job relations
            ->leftJoin('job_titles', 'applicants.job_title_id', '=', 'job_titles.id')
            ->leftJoin('job_categories', 'applicants.job_category_id', '=', 'job_categories.id')
            ->leftJoin('job_sources', 'applicants.job_source_id', '=', 'job_sources.id')

            ->leftJoinSub($cvNotesSubQuery, 'cv_notes', function ($join) {
                $join->on('applicants.id', '=', 'cv_notes.applicant_id')
                    ->on('applicants_pivot_sales.sale_id', '=', 'cv_notes.sale_id');
            })
            ->leftJoin('users', 'cv_notes.user_id', '=', 'users.id')

            // Latest module notes
            ->leftJoin(DB::raw("(
                SELECT module_noteable_id, MAX(created_at) as latest_note_created
                FROM module_notes
                WHERE module_noteable_type = 'Horsefly\\\\Applicant'
                GROUP BY module_noteable_id
            ) as latest_module_note"), 'applicants.id', '=', 'latest_module_note.module_noteable_id')

            // Filter
            ->where(function ($query) {
                $query->where('applicants.is_job_within_radius', true)
                    ->orWhereDate('applicants.created_at', '=', Carbon::now());
            })

            // Eager loading
            ->with(['jobTitle', 'jobCategory', 'jobSource', 'user']);



        // Filter by status if it's not empty
        switch ($statusFilter) {
            case 'interested':
                $model->whereNull('applicants_pivot_sales.applicant_id')
                    ->where("applicants.is_blocked", false)
                    ->where("applicants.is_temp_not_interested", false)
                    ->where('applicants.have_nursing_home_experience', false);
                break;
                
            case 'not interested':
                $model->where(function ($query) {
                        $query->where("applicants.is_temp_not_interested", true)
                            ->orWhereNotNull('applicants_pivot_sales.applicant_id');
                    })
                    ->where("applicants.is_blocked", false)
                    ->where("applicants.is_no_job", false);
                break;
                
            case 'blocked':
                $model->whereNull('applicants_pivot_sales.applicant_id')
                    ->where("applicants.is_blocked", true)
                    ->where("applicants.is_no_job", false)
                    ->where("applicants.is_temp_not_interested", false);
                break;
                
            case 'have nursing home exp':
                $model->whereNull('applicants_pivot_sales.applicant_id')
                    ->where("applicants.is_blocked", false)
                    ->where("applicants.is_temp_not_interested", false)
                    ->where('applicants.have_nursing_home_experience', true);
                break;
            default:
                $model->whereNull('applicants_pivot_sales.applicant_id')
                    ->where("applicants.is_blocked", false)
                    ->where("applicants.is_temp_not_interested", false)
                    ->where('applicants.have_nursing_home_experience', false);
                break;
        }
        
        $now = Carbon::today();
        switch($dateRangeFilter) {
            case 'last-7-days':
                $startDate = $now->copy()->subDays(16)->startOfDay();
                $endDate = $now->endOfDay();
                $model->whereBetween('applicants.updated_at', [$startDate, $endDate]);
                break;
            
            case 'last-21-days':
                $endDate = $now->copy()->subDays(16);
                $startDate = $endDate->copy()->subDays(21)->startOfDay();
                $model->whereBetween('applicants.updated_at', [$startDate, $endDate->endOfDay()]);
                break;
            
            case 'last-3-months':
                $endDate = $now->copy()->subDays(37);
                $startDate = $endDate->copy()->subMonths(3)->startOfDay();
                $model->whereBetween('applicants.updated_at', [$startDate, $endDate->endOfDay()]);
                break;
                
            case 'last-6-months':
                $endDate = $now->copy()->subMonths(3)->subDays(37);
                $startDate = $endDate->copy()->subMonths(6)->startOfDay();
                $model->whereBetween('applicants.updated_at', [$startDate, $endDate->endOfDay()]);
                break;
                
            case 'last-9-months':
                $endDate = $now->copy()->subMonths(9)->subDays(37);
                $startDate = $endDate->copy()->subMonths(9)->startOfDay();
                $model->whereBetween('applicants.updated_at', [$startDate, $endDate->endOfDay()]);
                break;
                
            case 'other':
                $cutoffDate = $now->copy()->subMonths(19)->subDays(7);
                $model->where('applicants.updated_at', '<', $cutoffDate);
                break;
            default:
                $startDate = $now->copy()->subDays(16)->startOfDay();
                $endDate = $now->endOfDay();
                $model->whereBetween('applicants.updated_at', [$startDate, $endDate]);
                break;
        }

        // Sorting logic
        if ($request->has('order')) {
            $orderColumn = $request->input('columns.' . $request->input('order.0.column') . '.data');
            $orderDirection = $request->input('order.0.dir', 'asc');

            // Handle special cases first
            if ($orderColumn === 'job_source') {
                $model->orderBy('applicants.job_source_id', $orderDirection);
            } elseif ($orderColumn === 'job_category') {
                $model->orderBy('applicants.job_category_id', $orderDirection);
            } elseif ($orderColumn === 'job_title') {
                $model->orderBy('applicants.job_title_id', $orderDirection);
            }
            // Default case for valid columns
            elseif ($orderColumn && $orderColumn !== 'checkbox') {
                $model->orderBy($orderColumn, $orderDirection);
            }
            // Fallback if no valid order column is found
            else {
                $model->orderBy('latest_module_note.latest_note_created', 'desc');

            }
        } else {
            // Default sorting when no order is specified
            $model->orderBy('latest_module_note.latest_note_created', 'desc');
        }

        if ($request->has('search.value')) {
            $searchTerm = (string) $request->input('search.value');

            if (!empty($searchTerm)) {
                $model->where(function ($query) use ($searchTerm) {
                    // Direct column searches
                    $query->where('applicants.applicant_name', 'LIKE', "%{$searchTerm}%")
                        ->orWhere('applicants.applicant_email', 'LIKE', "%{$searchTerm}%")
                        ->orWhere('applicants.applicant_postcode', 'LIKE', "%{$searchTerm}%")
                        ->orWhere('applicants.applicant_phone', 'LIKE', "%{$searchTerm}%")
                        ->orWhere('applicants.applicant_experience', 'LIKE', "%{$searchTerm}%")
                        ->orWhere('applicants.applicant_landline', 'LIKE', "%{$searchTerm}%");

                    // Relationship searches with explicit table names
                    $query->orWhereHas('jobTitle', function ($q) use ($searchTerm) {
                        $q->where('job_titles.name', 'LIKE', "%{$searchTerm}%");
                    });

                    $query->orWhereHas('jobCategory', function ($q) use ($searchTerm) {
                        $q->where('job_categories.name', 'LIKE', "%{$searchTerm}%");
                    });

                    $query->orWhereHas('jobSource', function ($q) use ($searchTerm) {
                        $q->where('job_sources.name', 'LIKE', "%{$searchTerm}%");
                    });
                    $query->orWhereHas('user', function ($q) use ($searchTerm) {
                        $q->where('users.name', 'LIKE', "%{$searchTerm}%");
                    });
                    $query->orWhereHas('module_note', function ($q) use ($searchTerm) {
                        $q->where('latest_module_note.latest_note_created', 'LIKE', "%{$searchTerm}%");
                    });
                });
            }
        }

        // Filter by type if it's not empty
        if ($typeFilter == 'specialist') {
            $model->where('applicants.job_type', 'specialist');
        } elseif ($typeFilter == 'regular') {
            $model->where('applicants.job_type', 'regular');
        }

        // Filter by type if it's not empty
        if ($categoryFilter) {
            $model->whereIn('applicants.job_category_id', $categoryFilter);
        }

        // Filter by type if it's not empty
        if ($titleFilter) {
            $model->whereIn('applicants.job_title_id', $titleFilter);
        }

        if ($request->ajax()) {
            return DataTables::eloquent($model)
                ->addColumn('checkbox', function ($applicant) {
                    return '<input type="checkbox" name="applicant_checkbox[]" class="applicant_checkbox" value="' . $applicant->id . '"/>';
                })
                ->addColumn("user_name", function ($applicant) {
                    return $applicant->user_name ?? '-';
                })
                ->addColumn('job_title', function ($applicant) {
                    return $applicant->jobTitle ? strtoupper($applicant->jobTitle->name) : '-';
                })
                ->addColumn('job_category', function ($sale) {
                    $type = $sale->job_type;
                    $stype  = $type && $type == 'specialist' ? '<br>(' . ucwords('Specialist') . ')' : '';
                    return $sale->jobCategory ? ucwords($sale->jobCategory->name) . $stype : '-';
                })
                ->addColumn('job_source', function ($applicant) {
                    return $applicant->jobSource ? ucwords($applicant->jobSource->name) : '-';
                })
                ->addColumn('applicant_name', function ($applicant) {
                    return $applicant->formatted_applicant_name; // Using accessor
                })
                ->addColumn('applicant_postcode', function ($applicant) {
                    $status_value = 'open';
                    $postcode = '';
                    if ($applicant->paid_status == 'close') {
                        $status_value = 'paid';
                    } else {
                        foreach ($applicant->cv_notes as $key => $value) {
                            if ($value->status == 'active') {
                                $status_value = 'sent';
                                break;
                            } elseif ($value->status == 'disable') {
                                $status_value = 'reject';
                            }
                        }
                    }

                    if($applicant->lat != null && $applicant->lng != null && $status_value == 'open' || $status_value == 'reject'){
                        $url = route('applicants.available_job', ['id' => $applicant->id, 'radius' => 15]);
                        $button = '<a href="'. $url .'" style="color:blue;" target="_blank">'. $applicant->formatted_postcode .'</a>'; // Using accessor
                    }else{
                        $button = $applicant->formatted_postcode;
                    }
                    return $button;
                })
                ->addColumn('applicant_email', function ($applicant) {
                    $email = '';
                    if($applicant->applicant_email_secondary){
                        $email = $applicant->applicant_email .'<br>'.$applicant->applicant_email_secondary; 
                    }else{
                        $email = $applicant->applicant_email;
                    }

                    return $email; // Using accessor
                })
                ->addColumn('applicant_notes', function ($applicant) {
                    $note = null;

                    // Ensure $applicant->module_note is iterable
                    if (!empty($applicant->module_note) && is_iterable($applicant->module_note)) {
                        foreach ($applicant->module_note as $item) {
                            if (!empty($item->details)) {
                                $note = $item;
                                break;
                            }
                        }
                    }

                    // Safely strip all tags except <strong> and <br>
                    $notes = $note ? strip_tags($note->details, '<strong><br>') : strip_tags($applicant->applicant_notes, '<strong><br>');

                    $status_value = 'open';
                    if ($applicant->paid_status == 'close') {
                        $status_value = 'paid';
                    } else {
                        if ($applicant->cv_note_status != null && $applicant->cv_note_status == 1) {
                            $status_value = 'sent';
                        } elseif ($applicant->cv_note_status != null && $applicant->cv_note_status == 0) {
                            $status_value = 'reject';
                        }
                    }

                    if ($applicant->is_blocked == 0 && $status_value == 'open' || $status_value == 'reject') {

                        $html = '
                            <a href="#" style="color:blue" onclick="addShortNotesModal(' . (int)$applicant->id . ')">
                                ' . $notes . '
                            </a>
                        ';
                    } else {
                        $html = $notes;
                    }

                    return $html;
                })
                ->addColumn('applicant_phone', function ($applicant) {
                    $strng = '';
                    if($applicant->applicant_landline){
                        $phone = '<strong>P:</strong> '.$applicant->applicant_phone;
                        $landline = '<strong>L:</strong> '.$applicant->applicant_landline;

                        $strng = $applicant->is_blocked ? "<span class='badge bg-dark'>Blocked</span>" : $phone .'<br>'. $landline;
                    }else{
                        $phone = '<strong>P:</strong> '.$applicant->applicant_phone;
                        $strng = $applicant->is_blocked ? "<span class='badge bg-dark'>Blocked</span>" : $phone;
                    }

                    return $strng;
                })
                ->addColumn('created_at', function ($applicant) {
                    $date = null;

                    // Ensure $applicant->module_note is iterable
                    if (!empty($applicant->module_note) && is_iterable($applicant->module_note)) {
                        foreach ($applicant->module_note as $item) {
                            if (!empty($item->created_at)) {
                                $date = $item->created_at;
                                break;
                            }
                        }
                    }

                    return $date
                        ? Carbon::parse($date)->format('d M Y h:i A')
                        : $applicant->formatted_updated_at; // Assuming you have an accessor
                })
                ->addColumn('applicant_experience', function ($applicant) {
                    $short = Str::limit(strip_tags($applicant->applicant_experience), 80);
                    $full = e($applicant->applicant_experience);
                    $id = 'exp-' . $applicant->id;

                    return '
                        <a href="#" class="text-primary" 
                        data-bs-toggle="modal" 
                        data-bs-target="#' . $id . '">
                            ' . $short . '
                        </a>

                        <!-- Modal -->
                        <div class="modal fade" id="' . $id . '" tabindex="-1" aria-labelledby="' . $id . '-label" aria-hidden="true">
                            <div class="modal-dialog modal-lg modal-dialog-scrollable">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="' . $id . '-label">Applicant Experience</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        ' . nl2br($full) . '
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-dark" data-bs-dismiss="modal">Close</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    ';
                })
                ->addColumn('applicant_resume', function ($applicant) {
                    $filePath = $applicant->applicant_cv;
                    $fileExists = $applicant->applicant_cv && Storage::disk('public')->exists($filePath);

                    if (!$applicant->is_blocked && $fileExists) {
                        return '<a href="' . asset('storage/' . $filePath) . '" title="Download CV" target="_blank" class="text-decoration-none">' .
                            '<iconify-icon icon="solar:download-square-bold" class="text-success fs-28"></iconify-icon></a>';
                    }

                    return '<button disabled title="CV Not Available" class="border-0 bg-transparent p-0">' .
                        '<iconify-icon icon="solar:download-square-bold" class="text-grey fs-28"></iconify-icon></button>';
                })
                ->addColumn('crm_resume', function ($applicant) {
                    $filePath = $applicant->updated_cv;
                    $fileExists = $applicant->updated_cv && Storage::disk('public')->exists($filePath);

                    if (!$applicant->is_blocked && $fileExists) {
                        return '<a href="' . asset('storage/' . $filePath) . '" title="Download Updated CV" target="_blank" class="text-decoration-none">' .
                            '<iconify-icon icon="solar:download-square-bold" class="text-primary fs-28"></iconify-icon></a>';
                    }

                    return '<button disabled title="CV Not Available" class="border-0 bg-transparent p-0">' .
                        '<iconify-icon icon="solar:download-square-bold" class="text-grey fs-28"></iconify-icon></button>';
                })
                ->addColumn('customStatus', function ($applicant) {
                    $status_value = 'open';
                    $color_class = 'bg-dark';
                    if ($applicant->paid_status == 'close') {
                        $status_value = 'paid';
                        $color_class = 'bg-info';
                    } else {
                        foreach ($applicant->cv_notes as $key => $value) {
                            if ($value->status == 1) {
                                $status_value = 'sent';
                                $color_class = 'bg-success';
                                break;
                            } elseif ($value->status == 0) {
                                $status_value = 'reject';
                                $color_class = 'bg-danger';
                            }
                        }
                    }

                    $status = '';
                    $status .= '<span class="badge ' . $color_class . '">';
                    $status .= strtoupper($status_value);
                    $status .= '</span>';
                    return $status;
                })
                ->addColumn('action', function ($applicant) {
                    $landline = $applicant->formatted_landline ?? '-';
                    $phone = $applicant->formatted_phone ?? '-';
                    $posted_date = $applicant->formatted_created_at;
                    $postcode = $applicant->formatted_postcode ?? '-';
                    $job_title = $applicant->jobTitle ? $applicant->jobTitle->name : '-';
                    $job_category = $applicant->jobCategory ? $applicant->jobCategory->name : '-';
                    $job_source = $applicant->jobSource ? $applicant->jobSource->name : '-';
                    $status = '';

                    if ($applicant->is_blocked) {
                        $status = '<span class="badge bg-dark">Blocked</span>';
                    } elseif ($applicant->status) {
                        $status = '<span class="badge bg-success">Active</span>';
                    } elseif ($applicant->is_no_response) {
                        $status = '<span class="badge bg-danger">No Response</span>';
                    } elseif ($applicant->is_circuit_busy) {
                        $status = '<span class="badge bg-warning">Circuit Busy</span>';
                    } elseif ($applicant->is_no_job) {
                        $status = '<span class="badge bg-secondary">No Job</span>';
                    } else {
                        $status = '<span class="badge bg-secondary">Inactive</span>';
                    }
                    $html = '';
                    $html .= '<div class="btn-group dropstart">
                            <button type="button" class="border-0 bg-transparent p-0" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <iconify-icon icon="solar:menu-dots-square-outline" class="align-middle fs-24 text-dark"></iconify-icon>
                            </button>
                            <ul class="dropdown-menu">
                                <li>
                                    <a class="dropdown-item" href="#" onclick="showDetailsModal(
                                        ' . (int)$applicant->id . ',
                                        \'' . addslashes(htmlspecialchars($applicant->applicant_name)) . '\',
                                        \'' . addslashes(htmlspecialchars($applicant->applicant_email ?? '-')) . '\',
                                        \'' . addslashes(htmlspecialchars($applicant->applicant_email_secondary ?? '-')) . '\',
                                        \'' . addslashes(htmlspecialchars($postcode)) . '\',
                                        \'' . addslashes(htmlspecialchars($landline)) . '\',
                                        \'' . addslashes(htmlspecialchars($phone)) . '\',
                                        \'' . addslashes(htmlspecialchars($job_title)) . '\',
                                        \'' . addslashes(htmlspecialchars($job_category)) . '\',
                                        \'' . addslashes(htmlspecialchars($job_source)) . '\',
                                        \'' . addslashes(htmlspecialchars($posted_date)) . '\',
                                        \'' . addslashes(htmlspecialchars($status)) . '\'
                                    )">View Details</a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="#" onclick="triggerFileInput(' . (int)$applicant->id . ')">Upload Applicant Resume</a>
                                    <input type="file" id="fileInput" style="display:none" accept=".pdf,.doc,.docx" onchange="uploadFile()">
                                </li>';
                        $html .= '<li>
                                <a class="dropdown-item" href="#" onclick="triggerCrmFileInput(' . (int)$applicant->id . ')">Upload CRM Resume</a>
                                <!-- Hidden File Input -->
                                <input type="file" id="crmfileInput" style="display:none" accept=".pdf,.doc,.docx" onchange="crmuploadFile()">
                            </li>';
                        $html .= '<li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="#" onclick="viewNotesHistory(' . (int)$applicant->id . ')">Notes History</a></li>
                            </ul>
                        </div>';

                        return $html;
                })
                // ->setRowClass(function ($applicant) {
                //     $row_class = '';

                //     // First check nursing home experience status
                //     if (isset($applicant->have_nursing_home_experience) && $applicant->have_nursing_home_experience == 0) {
                //         $row_class = 'have-no-nursing-home-exp';  // Specific class for no experience
                //     }elseif (isset($applicant->have_nursing_home_experience) && $applicant->have_nursing_home_experience == 1) {
                //         $row_class = 'have-nursing-home-exp';  // Specific class for no experience
                //     } else {
                //         if ($applicant->paid_status == 'close') {
                //             $row_class = 'class_dark';
                //         } elseif ($applicant->is_no_job == true) {
                //             $row_class = 'class_noJob';
                //         } else {
                //             /*** $applicant->paid_status == 'open' || $applicant->paid_status == 'pending' */
                //             foreach ($applicant->cv_notes as $key => $value) {
                //                 if ($value->status == 1) {
                //                     $row_class = 'class_success';
                //                     break;
                //                 } elseif ($value->status == 0) {
                //                     $row_class = 'class_danger';
                //                 }
                //             }
                //         }
                //     }
                //     return $row_class;
                // })
                ->rawColumns(['checkbox', 'applicant_email', 'applicant_experience', 'applicant_notes', 'applicant_postcode', 'applicant_landline', 'applicant_phone', 'job_title', 'applicant_resume', 'crm_resume', 'customStatus', 'job_category', 'job_source', 'action'])
                ->make(true);
        }
    }
    public function revertBlockedApplicant(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:applicants,id',
        ]);

        try {
            DB::beginTransaction();

            $applicantIds = $request->input('ids');
            $unblockedCount = 0;

            foreach ($applicantIds as $applicantId) {
                $applicant = Applicant::find($applicantId);

                if ($applicant && $applicant->is_blocked) {
                    $applicant->update([
                        'is_blocked' => false,
                        'applicant_notes' => 'Applicant has been unblocked',
                    ]);

                    // Deactivate previous active notes
                    ModuleNote::where('module_noteable_id', $applicant->id)
                        ->where('module_noteable_type', 'Horsefly\Applicant')
                        ->where('status', 1)
                        ->update(['status' => 0]);

                    // Create new module note
                    $moduleNote = ModuleNote::create([
                        'user_id' => Auth::id(),
                        'module_noteable_id' => $applicant->id,
                        'module_noteable_type' => 'Horsefly\Applicant',
                        'details' => 'Applicant has been unblocked',
                    ]);

                    $moduleNote->update([
                        'module_note_uid' => md5($moduleNote->id),
                    ]);

                    $unblockedCount++;
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "$unblockedCount applicant(s) unblocked successfully.",
            ], 200);
        } catch (\Exception $exception) {
            DB::rollBack();

            Log::error("Failed to revert blocked applicants: " . $exception->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Something went wrong! Please try again.',
            ], 500);
        }
    }
    public function revertNoJobApplicant(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:applicants,id',
        ]);

        try {
            DB::beginTransaction();

            $applicantIds = $request->input('ids');
            $revertedCount = 0;

            foreach ($applicantIds as $applicantId) {
                $applicant = Applicant::find($applicantId);

                if ($applicant && $applicant->is_no_job) {
                    $applicant->update([
                        'is_no_job' => false,
                        'applicant_notes' => 'No job applicant has been reverted.',
                    ]);

                    // Soft-close previous active notes
                    ModuleNote::where('module_noteable_id', $applicant->id)
                        ->where('module_noteable_type', 'Horsefly\Applicant')
                        ->where('status', 1)
                        ->update(['status' => 0]);

                    // Create new module note
                    $moduleNote = ModuleNote::create([
                        'user_id' => Auth::id(),
                        'module_noteable_id' => $applicant->id,
                        'module_noteable_type' => 'Horsefly\Applicant',
                        'details' => 'No job applicant has been reverted.',
                    ]);

                    $moduleNote->update([
                        'module_note_uid' => md5($moduleNote->id),
                    ]);

                    $revertedCount++;
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "$revertedCount applicant(s) reverted from 'No Job'."
            ]);
        } catch (\Exception $exception) {
            DB::rollBack();

            Log::error("Failed to revert no job applicants: " . $exception->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Something went wrong! Please try again.'
            ], 500);
        }
    }
    public function markAsNursingHomeExp(Request $request)
    {
        // Validate the request
        $request->validate([
            'selectedCheckboxes' => 'required|array',
            'selectedCheckboxes.*' => 'integer|exists:applicants,id',
        ]);

        try {
            DB::beginTransaction();

            $selectedIds = $request->input('selectedCheckboxes');

            // Bulk update applicants
            $updatedCount = Applicant::whereIn('id', $selectedIds)
                ->update(['have_nursing_home_experience' => true]);

            // Disable existing module notes for those applicants
            ModuleNote::whereIn('module_noteable_id', $selectedIds)
                ->where('module_noteable_type', 'Horsefly\Applicant')
                ->where('status', 1)
                ->update(['status' => 0]);

            // Create new module notes
            foreach ($selectedIds as $id) {
                $moduleNote = ModuleNote::create([
                    'user_id' => Auth::id(),
                    'module_noteable_id' => $id,
                    'module_noteable_type' => 'Horsefly\Applicant',
                    'details' => 'Applicant has been marked as having nursing home experience',
                ]);

                $moduleNote->update([
                    'module_note_uid' => md5($moduleNote->id)
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "$updatedCount applicant(s) marked as having nursing home experience.",
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to mark applicants as nursing home experience: " . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while updating applicants.',
            ], 500);
        }
    }
    public function markAsNoNursingHomeExp(Request $request)
    {
        // Validate the request
        $request->validate([
            'selectedCheckboxes' => 'required|array',
            'selectedCheckboxes.*' => 'integer|exists:applicants,id',
        ]);

        try {
            DB::beginTransaction();

            $selectedIds = $request->input('selectedCheckboxes');

            // Bulk update applicants
            $updatedCount = Applicant::whereIn('id', $selectedIds)
                ->update(['have_nursing_home_experience' => false]);

            // Disable previous active module notes
            ModuleNote::whereIn('module_noteable_id', $selectedIds)
                ->where('module_noteable_type', 'Horsefly\Applicant')
                ->where('status', 1)
                ->update(['status' => 0]);

            // Create new module notes
            foreach ($selectedIds as $id) {
                $moduleNote = ModuleNote::create([
                    'user_id' => Auth::id(),
                    'module_noteable_id' => $id,
                    'module_noteable_type' => 'Horsefly\Applicant',
                    'details' => 'Applicant has been marked as having no nursing home experience',
                ]);

                $moduleNote->update([
                    'module_note_uid' => md5($moduleNote->id)
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "$updatedCount applicant(s) marked as having no nursing home experience.",
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to mark applicants as no nursing home experience: " . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while updating applicants.',
            ], 500);
        }
    }
    public function markApplicantNotInterestedOnSale(Request $request)
    {
        try {
            DB::beginTransaction();

            $user = Auth::user();

            $applicant_id = $request->input('applicant_id');
            $sale_id = $request->input('sale_id');
            $details = $request->input('details');
            $notes = $details . ' --- By: ' . $user->name . ' Date: ' . now()->format('d-m-Y');

            // Create pivot sale entry
            $pivotSale = ApplicantPivotSale::create([
                'applicant_id' => $applicant_id,
                'sale_id' => $sale_id
            ]);

            $pivotSale->update([
                'pivot_uid' => md5($pivotSale->id)
            ]);

            // Add notes for range
            $notesForRange = NotesForRangeApplicant::create([
                'applicants_pivot_sales_id' => $pivotSale->id,
                'reason' => $notes,
            ]);

            $notesForRange->update([
                'range_uid' => md5($notesForRange->id)
            ]);

            // Disable previous active module notes
            ModuleNote::where([
                'module_noteable_id' => $applicant_id,
                'module_noteable_type' => 'Horsefly\Applicant',
                'status' => 1
            ])->update(['status' => 0]);

            // Create new module note
            $moduleNote = ModuleNote::create([
                'details' => $notes,
                'user_id' => $user->id,
                'module_noteable_id' => $applicant_id,
                'module_noteable_type' => 'Horsefly\Applicant'
            ]);

            $moduleNote->update([
                'module_note_uid' => md5($moduleNote->id)
            ]);

            DB::commit();
            return redirect()->back()->with('success', 'Applicant marked as not interested successfully.');
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to mark applicant not interested: ' . $e->getMessage());

            return redirect()->back()->with('error', 'Something went wrong. Please try again.');
        }
    }
    public function markApplicantCallback(Request $request)
    {
        $request->validate([
            'applicant_id' => 'required|integer|exists:applicants,id',
            'sale_id' => 'nullable|integer|exists:sales,id',
            'details' => 'nullable|string|max:1000',
        ]);

        $user = Auth::user();

        $applicant_id = $request->input('applicant_id');
        $sale_id = $request->input('sale_id');
        $details = $request->input('details');
        $notes = $details . ' --- By: ' . $user->name . ' Date: ' . now()->format('d-m-Y');

        try {
            DB::beginTransaction();

            // Handle pivot sale if sale_id is given
            if ($sale_id) {
                $pivotSale = ApplicantPivotSale::where('applicant_id', $applicant_id)
                    ->where('sale_id', $sale_id)
                    ->first();

                if ($pivotSale) {
                    // Delete range notes
                    NotesForRangeApplicant::where('applicants_pivot_sales_id', $pivotSale->id)->delete();
                    $pivotSale->delete();
                }
            }

            // Disable previous callback/revert_callback notes
            ApplicantNote::where('applicant_id', $applicant_id)
                ->whereIn('moved_tab_to', ['callback', 'revert_callback'])
                ->update(['status' => false]);

            // Create new ApplicantNote
            $applicantNote = ApplicantNote::create([
                'user_id' => $user->id,
                'applicant_id' => $applicant_id,
                'details' => $notes,
                'moved_tab_to' => 'callback',
            ]);

            $applicantNote->update([
                'note_uid' => md5($applicantNote->id)
            ]);

            // Mark applicant as callback enabled
            Applicant::where('id', $applicant_id)
                ->update(['is_callback_enable' => true]);

            // Disable previous active module notes
            ModuleNote::where([
                'module_noteable_id' => $applicant_id,
                'module_noteable_type' => 'Horsefly\Applicant',
                'status' => 1
            ])->update(['status' => 0]);

            // Create new module note
            $moduleNote = ModuleNote::create([
                'details' => $notes,
                'user_id' => $user->id,
                'module_noteable_id' => $applicant_id,
                'module_noteable_type' => 'Horsefly\Applicant'
            ]);

            $moduleNote->update([
                'module_note_uid' => md5($moduleNote->id)
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Callback marked successfully!',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to mark applicant callback: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Something went wrong. Please try again.',
            ], 500);
        }
    }
    public function exportDirectApplicantsEmails(Request $request)
    {
        $emailData = $request->input('app_email'); // string: "a@a.com, b@b.com"
        $dataEmail = array_filter(array_map('trim', explode(',', $emailData))); // remove spaces and empty values

        return Excel::download(new EmailExport($dataEmail), 'applicants.csv');
    }
}