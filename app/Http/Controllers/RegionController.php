<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Horsefly\Sale;
use Horsefly\Office;
use Horsefly\Unit;
use Horsefly\Applicant;
use Horsefly\Region;
use Horsefly\ModuleNote;
use Horsefly\JobTitle;
use Horsefly\JobSource;
use Horsefly\CVNote;
use Horsefly\History;
use Horsefly\JobCategory;
use Horsefly\ApplicantPivotSale;
use Horsefly\NotesForRangeApplicant;
use App\Exports\ApplicantsExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Carbon\Carbon;

class RegionController extends Controller
{
    public function __construct()
    {
        //
    }
    public function resourcesIndex()
    {
        return view('regions.resources');
    }
    public function salesIndex()
    {
        return view('regions.sales');
    }
    public function getApplicantsByRegions(Request $request)
    {
        $regionFilter = $request->input('region_filter', ''); // Default is empty (no filter)
        $typeFilter = $request->input('type_filter', ''); // Default is empty (no filter)
        $categoryFilter = $request->input('category_filter', ''); // Default is empty (no filter)
        $titleFilter = $request->input('title_filter', ''); // Default is empty (no filter)
        $searchTerm = $request->input('search', ''); // This will get the search query
        
        if($regionFilter){
            $reg = Region::where('id', $regionFilter)->first();
        }else{
            $reg = Region::first();
        }

       $district = $reg['districts_code'] ?? null;

        $model = Applicant::query()
            ->with('cv_notes')
            ->select([
                'applicants.*',
                'job_titles.name as job_title_name',
                'job_categories.name as job_category_name',
                'job_sources.name as job_source_name'
            ])
            ->where('applicants.status', 1)
            ->where('applicants.is_blocked', false)
            ->leftJoin('applicants_pivot_sales', 'applicants.id', '=', 'applicants_pivot_sales.applicant_id')
            ->leftJoin('job_titles', 'applicants.job_title_id', '=', 'job_titles.id')
            ->leftJoin('job_categories', 'applicants.job_category_id', '=', 'job_categories.id')
            ->leftJoin('job_sources', 'applicants.job_source_id', '=', 'job_sources.id')
            ->leftJoin('cv_notes', 'cv_notes.applicant_id', '=', 'applicants.id')
            ->with(['jobTitle', 'jobCategory', 'jobSource'])
            ->whereRaw("UPPER(TRIM(applicants.applicant_postcode)) REGEXP '^($district)[0-9]'");

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
            elseif ($orderColumn && $orderColumn !== 'DT_RowIndex') {
                $model->orderBy($orderColumn, $orderDirection);
            }
            // Fallback if no valid order column is found
            else {
                $model->orderBy('cv_notes.created_at', 'desc');
            }
        } else {
            // Default sorting when no order is specified
            $model->orderBy('cv_notes.created_at', 'desc');
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
            $model->where('applicants.job_category_id', $categoryFilter);
        }

        // Filter by type if it's not empty
        if ($titleFilter) {
            $model->where('applicants.job_title_id', $titleFilter);
        }

        if ($request->ajax()) {
            return DataTables::eloquent($model)
                ->addIndexColumn() // This will automatically add a serial number to the rows
                ->addColumn('job_title', function ($applicant) {
                    return $applicant->jobTitle ? $applicant->jobTitle->name : '-';
                })
                ->addColumn('job_category', function ($sale) {
                    $type = $sale->job_type;
                    $stype  = $type && $type == 'specialist' ? '<br>(' . ucwords('Specialist') . ')' : '';
                    return $sale->jobCategory ? $sale->jobCategory->name . $stype : '-';
                })
                ->addColumn('job_source', function ($applicant) {
                    return $applicant->jobSource ? $applicant->jobSource->name : '-';
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
                            if ($value->status == 1) {
                                $status_value = 'sent';
                                break;
                            } elseif ($value->status == 0) {
                                $status_value = 'reject';
                            }
                        }
                    }
                    if ($status_value == 'open' || $status_value == 'reject') {
                        $postcode .= '<a href="/available-jobs/' . $applicant->id . '">';
                        $postcode .= $applicant->formatted_postcode;
                        $postcode .= '</a>';
                    } else {
                        $postcode .= $applicant->formatted_postcode;
                    }

                    return $postcode;
                })
                ->addColumn('applicant_notes', function ($applicant) {
                    $notes = htmlspecialchars($applicant->applicant_notes, ENT_QUOTES, 'UTF-8');
                    $name = htmlspecialchars($applicant->applicant_name, ENT_QUOTES, 'UTF-8');
                    $postcode = htmlspecialchars($applicant->applicant_postcode, ENT_QUOTES, 'UTF-8');

                    // Tooltip content with additional data-bs-placement and title
                    return '<a href="#" title="View Note" onclick="showNotesModal(\'' . $notes . '\', \'' . $name . '\', \'' . $postcode . '\')">
                                <iconify-icon icon="solar:eye-scan-bold" class="text-primary fs-24"></iconify-icon>
                            </a>
                            <a href="#" title="Add Short Note" onclick="addShortNotesModal(\'' . $applicant->id . '\')">
                                <iconify-icon icon="solar:clipboard-add-linear" class="text-warning fs-24"></iconify-icon>
                            </a>';
                })
                ->addColumn('applicant_phone', function ($applicant) {
                    return $applicant->is_blocked ? "<span class='badge bg-dark'>Blocked</span>" : $applicant->applicant_phone;
                })
                ->addColumn('applicant_landline', function ($applicant) {
                    return $applicant->is_blocked ? "<span class='badge bg-dark'>Blocked</span>" : $applicant->applicant_landline;
                })
                ->addColumn('updated_at', function ($applicant) {
                    if (!empty($applicant->cv_notes) && count($applicant->cv_notes) > 0) {
                        // Get the latest cv_note by created_at
                        $latestNote = $applicant->cv_notes->sortByDesc('created_at')->first();
                        return $latestNote ? Carbon::parse($latestNote->created_at)->format('d M Y, h:i A') : '';
                    } else {
                        return Carbon::parse($applicant->updated_at)->format('d M Y, h:i A');
                    }
                })
                ->addColumn('resume', function ($applicant) {
                    if (!$applicant->is_blocked) {
                        $applicant_cv = (file_exists('public/storage/uploads/resume/' . $applicant->applicant_cv) || $applicant->applicant_cv != null)
                            ? '<a href="' . asset('storage/' . $applicant->applicant_cv) . '" title="Download CV" target="_blank">
                            <iconify-icon icon="solar:download-square-bold" class="text-success fs-28"></iconify-icon></a>'
                            : '<iconify-icon icon="solar:download-square-bold" class="text-light-grey fs-28"></iconify-icon>';

                        $updated_cv = (file_exists('public/storage/uploads/resume/' . $applicant->updated_cv) || $applicant->updated_cv != null)
                            ? '<a href="' . asset('storage/' . $applicant->updated_cv) . '" title="Download Updated CV" target="_blank">
                            <iconify-icon icon="solar:download-square-bold" class="text-primary fs-28"></iconify-icon></a>'
                            : '<iconify-icon icon="solar:download-square-bold" class="text-grey fs-28"></iconify-icon>';
                    } else {
                        $applicant_cv = '<iconify-icon icon="solar:download-square-bold" class="text-grey fs-28"></iconify-icon>';
                        $updated_cv = '<iconify-icon icon="solar:download-square-bold" class="text-grey fs-28"></iconify-icon>';
                    }

                    return $applicant_cv . ' ' . $updated_cv;
                })
                ->addColumn('customStatus', function ($applicant) {
                    $status_value = 'open';
                    $color_class = 'bg-success';
                    if ($applicant->paid_status == 'close') {
                        $status_value = 'paid';
                        $color_class = 'bg-primary';
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
                    $status .= '<span class="badge '.$color_class.'">';
                    $status .= strtoupper($status_value);
                    $status .= '</span>';

                    return $status;
                })
                ->addColumn('action', function ($applicant) {
                    $landline = $applicant->formatted_landline;
                    $phone = $applicant->formatted_phone;
                    $postcode = $applicant->formatted_postcode;
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

                    return '<div class="btn-group dropstart">
                            <button type="button" class="border-0 bg-transparent p-0" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <iconify-icon icon="solar:menu-dots-square-outline" class="align-middle fs-24 text-dark"></iconify-icon>
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="#" onclick="showDetailsModal(
                                    ' . $applicant->id . ',
                                    \'' . addslashes(htmlspecialchars($applicant->applicant_name)) . '\',
                                    \'' . addslashes(htmlspecialchars($applicant->applicant_email)) . '\',
                                    \'' . addslashes(htmlspecialchars($applicant->applicant_email_secondary)) . '\',
                                    \'' . addslashes(htmlspecialchars($postcode)) . '\',
                                    \'' . addslashes(htmlspecialchars($landline)) . '\',
                                    \'' . addslashes(htmlspecialchars($phone)) . '\',
                                    \'' . addslashes(htmlspecialchars($job_title)) . '\',
                                    \'' . addslashes(htmlspecialchars($job_category)) . '\',
                                    \'' . addslashes(htmlspecialchars($job_source)) . '\',
                                    \'' . addslashes(htmlspecialchars($status)) . '\'
                                )">View</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="#" onclick="viewNotesHistory(' . $applicant->id . ')">Notes History</a></li>
                            </ul>
                        </div>';
                })
                ->rawColumns(['applicant_notes', 'applicant_postcode', 'applicant_landline', 'applicant_phone', 'job_title', 'resume', 'customStatus', 'job_category', 'job_source', 'action'])
                ->make(true);
        }
    }
    public function getSalesByRegions(Request $request)
    {
        $statusFilter = $request->input('status_filter', '');
        $regionFilter = $request->input('region_filter', ''); // Default is empty (no filter)
        $typeFilter = $request->input('type_filter', ''); // Default is empty (no filter)
        $categoryFilter = $request->input('category_filter', ''); // Default is empty (no filter)
        $titleFilter = $request->input('title_filter', ''); // Default is empty (no filter)
        $limitCountFilter = $request->input('cv_limit_filter', ''); // Default is empty (no filter)
        $officeFilter = $request->input('office_filter', ''); // Default is empty (no filter)

        if($regionFilter){
            $reg = Region::where('id', $regionFilter)->first();
        }else{
            $reg = Region::first();
        }
        
        $district = $reg['districts_code'] ?? null;

        $model = Sale::query()
            ->select([
            'sales.*',
            'job_titles.name as job_title_name',
            'job_categories.name as job_category_name',
            'offices.office_name as office_name',
            'units.unit_name as unit_name',
            'users.name as user_name',
            ])
            ->leftJoin('job_titles', 'sales.job_title_id', '=', 'job_titles.id')
            ->leftJoin('job_categories', 'sales.job_category_id', '=', 'job_categories.id')
            ->leftJoin('offices', 'sales.office_id', '=', 'offices.id')
            ->leftJoin('units', 'sales.unit_id', '=', 'units.id')
            ->leftJoin('users', 'sales.user_id', '=', 'users.id')
            ->with(['jobTitle', 'jobCategory', 'unit', 'office', 'user'])
            ->selectRaw(DB::raw("(SELECT COUNT(*) FROM cv_notes WHERE cv_notes.sale_id = sales.id AND cv_notes.status = 1) as no_of_sent_cv"))
            ->whereRaw("UPPER(TRIM(sales.sale_postcode)) REGEXP '^($district)[0-9]'");

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

                    $query->orWhereHas('user', function ($q) use ($likeSearch) {
                        $q->where('users.name', 'LIKE', "%{$likeSearch}%");
                    });
                });
            }
        }

        // Filter by type if it's not empty
        if ($typeFilter == 'specialist') {
            $model->where('sales.job_type', 'specialist');
        } elseif ($typeFilter == 'regular') {
            $model->where('sales.job_type', 'regular');
        }

        // Filter by status if it's not empty
        switch ($statusFilter) {
            case 'active':
                $model->where('sales.status', 1);
                break;
                
            case 'closed':
                $model->where('sales.status', 0)
                    ->where('sales.is_on_hold', 0);
                break;
                
            case 'pending':
                $model->where('sales.status', 2);
                break;
                
            case 'rejected':
                $model->where('sales.status', 3);
                break;
                
            case 'on hold':
                $model->where('sales.is_on_hold', true);
                break;
                
            // Optional: default case if none match
            default:
                // You might want to handle unexpected values here
                // For example, show all active sales by default:
                // $model->where('sales.status', 1);
                break;
        }
       
        // Filter by category if it's not empty
        if ($officeFilter) {
            $model->where('sales.office_id', $officeFilter);
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
            $model->where('sales.job_category_id', $categoryFilter);
        }
       
        // Filter by category if it's not empty
        if ($titleFilter) {
            $model->where('sales.job_title_id', $titleFilter);
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
                    $office_id = $sale->office_id;
                    $office = Office::find($office_id);
                    return $office ? $office->office_name : '-';
                })
                ->addColumn('unit_name', function ($sale) {
                    $unit_id = $sale->unit_id;
                    $unit = Unit::find($unit_id);
                    return $unit ? $unit->unit_name : '-';
                })
                ->addColumn('job_title', function ($sale) {
                    return $sale->jobTitle ? $sale->jobTitle->name : '-';
                })
                 ->addColumn('open_date', function ($sale) {
                    return $sale->open_date ? Carbon::parse($sale->open_date)->format('d M Y, h:i A') : '-'; // Using accessor
                })
                ->addColumn('job_category', function ($sale) {
                    $type = $sale->job_type;
                    $stype  = $type && $type == 'specialist' ? '<br>(' . ucwords('Specialist') . ')' : '';
                    return $sale->jobCategory ? $sale->jobCategory->name . $stype : '-';
                })
                ->addColumn('sale_postcode', function ($sale) {
                    return $sale->formatted_postcode; // Using accessor
                })
                ->addColumn('updated_at', function ($sale) {
                    return $sale->formatted_updated_at; // Using accessor
                })
                ->addColumn('cv_limit', function ($sale) {
                    $status = $sale->no_of_sent_cv == $sale->cv_limit ? '<span class="badge w-100 bg-danger" style="font-size:90%" >' . $sale->no_of_sent_cv . '/' . $sale->cv_limit . '<br>Limit Reached</span>' : "<span class='badge w-100 bg-primary' style='font-size:90%'>" . ((int)$sale->cv_limit - (int)$sale->no_of_sent_cv . '/' . (int)$sale->cv_limit) . "<br>Limit Remains</span>";
                    return $status;
                })
                ->addColumn('sale_notes', function ($sale) {
                    $notes = nl2br(htmlspecialchars($sale->sale_notes, ENT_QUOTES, 'UTF-8'));
                    $notes = $notes ? $notes : 'N/A';
                    $postcode = htmlspecialchars($sale->sale_postcode, ENT_QUOTES, 'UTF-8');
                    $unit = Unit::find($sale->unit_id);
                    $unit_name = $unit ? $unit->unit_name : '-';
                    // Tooltip content with additional data-bs-placement and title
                    return '<a href="#" title="View Note" onclick="showNotesModal(\'' . $notes . '\', \'' . $unit_name . '\', \'' . $postcode . '\')">
                                <iconify-icon icon="solar:eye-scan-bold" class="text-primary fs-24"></iconify-icon>
                            </a>';
                })
                ->addColumn('status', function ($sale) {
                    $status = '';
                    if ($sale->status == 1) {
                        $status = '<span class="badge bg-success">Active</span>';
                    } elseif ($sale->status == 0 && $sale->is_on_hold == 0) {
                        $status = '<span class="badge bg-danger">Closed</span>';
                    } elseif ($sale->status == 2) {
                        $status = '<span class="badge bg-warning">Pending</span>';
                    } elseif ($sale->status == 3) {
                        $status = '<span class="badge bg-danger">Rejected</span>';
                    }

                    return $status;
                })
                ->addColumn('action', function ($sale) {
                    $postcode = $sale->formatted_postcode;
                    $office_id = $sale->office_id;
                    $office = Office::find($office_id);
                    $office_name = $office ? $office->office_name : '-';
                    $unit_id = $sale->unit_id;
                    $unit = Unit::find($unit_id);
                    $unit_name = $unit ? $unit->unit_name : '-';
                    $status = '';
                    $jobTitle = $sale->jobTitle ? $sale->jobTitle->name : '-';
                    $type = $sale->job_type;
                    $stype  = $type && $type == 'specialist' ? '<br>(' . ucwords($type) . ')' : '';
                    $jobCategory = $sale->jobCategory ? $sale->jobCategory->name . $stype : '-';

                    if ($sale->status == '1') {
                        $status = '<span class="badge bg-success">Active</span>';
                    } elseif ($sale->status == '0' && $sale->is_on_hold == '0') {
                        $status = '<span class="badge bg-danger">Closed</span>';
                    } elseif ($sale->status == '2') {
                        $status = '<span class="badge bg-warning">Pending</span>';
                    } elseif ($sale->status == '3') {
                        $status = '<span class="badge bg-danger">Rejected</span>';
                    }

                    $position_type = strtoupper(str_replace('-',' ',$sale->position_type));
                    $position = '<span class="badge bg-primary">'. $position_type .'</span>';

                    $action = '';
                    $action = '<div class="btn-group dropstart">
                                <button type="button" class="border-0 bg-transparent p-0" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    <iconify-icon icon="solar:menu-dots-square-outline" class="align-middle fs-24 text-dark"></iconify-icon>
                                </button>
                                <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="#" onclick="showDetailsModal(
                                    ' . $sale->id . ',
                                    \'' . addslashes(htmlspecialchars($office_name)) . '\',
                                    \'' . addslashes(htmlspecialchars($unit_name)) . '\',
                                    \'' . addslashes(htmlspecialchars($postcode)) . '\',
                                    \'' . addslashes(htmlspecialchars($jobCategory)) . '\',
                                    \'' . addslashes(htmlspecialchars($jobTitle)) . '\',
                                    \'' . addslashes(htmlspecialchars($status)) . '\',
                                    \'' . addslashes(htmlspecialchars($sale->timing)) . '\',
                                    \'' . addslashes(htmlspecialchars($sale->experience)) . '\',
                                    \'' . addslashes(htmlspecialchars($sale->salary)) . '\',
                                    \'' . addslashes(htmlspecialchars($position)) . '\',
                                    \'' . addslashes(htmlspecialchars($sale->qualification)) . '\',
                                    \'' . addslashes(htmlspecialchars($sale->benefits)) . '\',
                                    )">View</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="#" onclick="viewSaleDocuments(' . $sale->id . ')">View Documents</a></li>
                                    <li><a class="dropdown-item" href="#" onclick="viewNotesHistory(' . $sale->id . ')">Notes History</a></li>
                                    <li><a class="dropdown-item" href="#" onclick="viewManagerDetails(' . $sale->unit_id . ')">Manager Details</a></li>
                                </ul>
                            </div>';

                    return $action;
                })
                ->rawColumns(['sale_notes', 'job_title', 'cv_limit', 'open_date', 'job_category', 'office_name', 'unit_name', 'status', 'action', 'statusFilter'])
                ->make(true);
        }
    }
}
