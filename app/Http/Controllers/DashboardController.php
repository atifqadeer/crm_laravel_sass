<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;
use Horsefly\User;
use Horsefly\Sale;
use Horsefly\Office;
use Horsefly\Applicant;
use Horsefly\Audit;
use Horsefly\CVNote;
use Horsefly\CrmNote;
use Horsefly\History;
use App\Http\Controllers\Controller;
use Horsefly\LoginDetail;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Hash;
use App\Exports\UsersExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function __construct()
    {
    }
    public function getUsersForDashboard(Request $request)
    {
        $model = User::query()
            ->leftJoin('model_has_roles', function ($join) {
                $join->on('users.id', '=', 'model_has_roles.model_id')
                    ->where('model_has_roles.model_type', '=', User::class);
            })
            ->leftJoin('roles', 'model_has_roles.role_id', '=', 'roles.id')
            ->select('users.*', 'roles.name as role_name'); // Add alias for sorting


        // Sorting logic
        if ($request->has('order')) {
            $orderColumn = $request->input('columns.' . $request->input('order.0.column') . '.data');
            $orderDirection = $request->input('order.0.dir', 'asc');

            if ($orderColumn === 'role') {
                $model->orderBy('role_name', $orderDirection);
            } elseif ($orderColumn && $orderColumn !== 'DT_RowIndex') {
                $model->orderBy($orderColumn, $orderDirection);
            } else {
                $model->orderBy('users.created_at', 'desc');
            }
        } else {
            // Default sorting when no order is specified
            $model->orderBy('users.created_at', 'desc');
        }

        if ($request->ajax()) {
            return DataTables::eloquent($model)
                ->addIndexColumn() // This will automatically add a serial number to the rows
                ->addColumn('name', function ($user) {
                    return '<img src="/images/users/avatar-2.jpg" class="avatar-sm rounded-circle me-2"
                                                alt="...">' . $user->formatted_name; // Using accessor
                })
                ->addColumn('role_name', function ($user) {
                    $role = $user->role_name; // returns the first (or only) role name
                    return $role ? ucwords($role) : '-';
                })
                ->addColumn('created_at', function ($user) {
                    return $user->formatted_created_at; // Using accessor
                })
                ->addColumn('updated_at', function ($user) {
                    return $user->formatted_updated_at; // Using accessor
                })
                ->addColumn('is_active', function ($user) {
                    $status = '';
                    if ($user->is_active) {
                        $status = '<span class="badge bg-success-subtle text-success py-1 px-2 fs-12">Active</span>';
                    } else {
                        $status = '<span class="badge bg-danger-subtle text-danger py-1 px-2 fs-12">Inactive</span>';
                    }

                    return $status;
                })
                ->addColumn('action', function ($user) {
                    $name = $user->formatted_name;
                    $email = $user->email;
                    $roleName = ucwords($user->role_name);
                    $status = '';

                    if ($user->is_active) {
                        $status = '<span class="badge bg-success">Active</span>';
                    } else {
                        $status = '<span class="badge bg-danger">Inactive</span>';
                    }
                    $html = '';
                    $html .= '<div class="d-flex gap-2 align-items-center">
                                <a href="#!" class="btn btn-light btn-sm" onclick="showDetailsModal(
                                        \'' . (int)$user->id . '\',
                                        \'' . addslashes(htmlspecialchars($name)) . '\',
                                        \'' . addslashes(htmlspecialchars($email)) . '\',
                                        \'' . addslashes(htmlspecialchars($roleName)) . '\',
                                        \'' . addslashes(htmlspecialchars($status)) . '\'
                                    )">
                                    <iconify-icon icon="solar:eye-broken"
                                                class="align-middle fs-18"></iconify-icon>
                                </a>
                                <a href="#!" class="btn btn-light btn-sm" onclick="showStatisticsModal(
                                        \'' . (int)$user->id . '\'
                                    )">
                                    <iconify-icon icon="solar:square-arrow-right-up-bold" class="text-info align-middle fs-18"></iconify-icon>
                                </a>
                            </div>';

                    return $html;
                })
                ->rawColumns(['name', 'is_active', 'action', 'role_name'])
                ->make(true);
        }
    }
    public function getUserStatistics(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'user_key' => 'required|exists:users,id',
                'date_range_filter' => 'required',
            ]
        );

        if ($validator->passes()) {
            [$start_date, $end_date] = explode('|', $request->input('date_range_filter'));
            $start_date = trim($start_date) . ' 00:00:00';
            $end_date = trim($end_date) . ' 23:59:59';

            $user_id = $request->input('user_key');
            $userWithRole = User::leftJoin('model_has_roles', function ($join) {
                        $join->on('users.id', '=', 'model_has_roles.model_id')
                            ->where('model_has_roles.model_type', '=', User::class);
                    })
                    ->leftJoin('roles', 'model_has_roles.role_id', '=', 'roles.id')
                    ->where('users.id', $user_id)
                    ->select('users.*', 'roles.name as role_name')
                    ->firstOrFail();

            $user_role = $userWithRole->role_name ?? '';
            $user_name = $userWithRole->name ?? '';

            $quality_stats['cvs_sent'] = 0;
            $quality_stats['send_cvs_from_cv_notes'] = [];
            $prev_user_stats['CRM_start_date'] = 0;
            $prev_user_stats['CRM_invoice'] = 0;
            $prev_user_stats['CRM_paid'] = 0;
            $user_stats_updated = '';

            if (in_array($user_role, ['Sales', 'Sale and CRM'])) {
                $sales = Sale::where('user_id', $user_id)
                                ->whereIn('status', [1, 0])
                                ->whereBetween('created_at', [$start_date, $end_date])
                                ->get();

                $user_stats['close_sales'] = Audit::join('sales', 'sales.id', '=', 'audits.auditable_id')
                    ->where(['audits.message' => 'sale-closed', 'audits.auditable_type' => 'Horsefly\\Sale'])
                    ->where('sales.user_id', '=', $user_id)
                    ->whereBetween('sales.created_at', [$start_date, $end_date])
                    ->whereBetween('audits.created_at', [$start_date, $end_date])->count();

                $user_stats['open_sales'] = $sales->count() - $user_stats['close_sales'];
                $user_stats['psl_offices'] = Office::where(['status' => 1, 'office_type' => 'psl', 'user_id' => $user_id])
                                                ->whereBetween('created_at', [$start_date, $end_date])
                                                ->count();

                $user_stats['non_psl_offices'] = Office::where(['status' => 1, 'office_type' => 'non psl', 'user_id' => $user_id])
                                                    ->whereBetween('created_at', [$start_date, $end_date])
                                                    ->count();

                foreach ($sales as $sale) {
                    $send_cvs_from_cv_notes = CVNote::where('sale_id', '=', $sale->id)
                        ->whereBetween('updated_at', [$start_date, $end_date])
                        ->select('applicant_id', 'sale_id')
                        ->get();

                    $user_stats_updated = CVNote::where('user_id', '=', $user_id)
                        ->select('applicant_id', 'sale_id')
                        ->where('created_at', '<', $start_date)
                        ->whereBetween('updated_at', [$start_date, $end_date])
                        ->get();

                    foreach ($send_cvs_from_cv_notes as $send_cvs_from_cv_note) {
                        $quality_stats['send_cvs_from_cv_notes'][] = $send_cvs_from_cv_note;


                        /*** Quality  CVs*/
                        $quality_stats['cvs_sent']++;
                    }
                }
            } else {
                $quality_stats['send_cvs_from_cv_notes'] = CVNote::where('user_id', '=', $user_id)
                                ->whereBetween('created_at', [$start_date, $end_date])
                                ->select('applicant_id', 'sale_id')
                                ->get();

                $quality_stats['cvs_sent'] = $quality_stats['send_cvs_from_cv_notes']->count();

                $user_stats_updated = CVNote::where('user_id', '=', $user_id)
                    ->select('applicant_id', 'sale_id')
                    ->where('created_at', '<', $start_date)
                    ->whereBetween('updated_at', [$start_date, $end_date])
                    ->get();

            }

            $quality_stats['cvs_rejected'] = 0;
            $quality_stats['cvs_cleared'] = 0;

            $user_stats['CRM_sent_cvs'] = 0;
            $user_stats['CRM_rejected_cv'] = 0;
            $user_stats['CRM_request'] = 0;
            $user_stats['CRM_rejected_by_request'] = 0;
            $user_stats['CRM_confirmation'] = 0;
            $user_stats['CRM_rebook'] = 0;
            $user_stats['CRM_attended'] = 0;
            $user_stats['CRM_not_attended'] = 0;
            $user_stats['CRM_start_date'] = 0;
            $user_stats['CRM_start_date_hold'] = 0;
            $user_stats['CRM_declined'] = 0;
            $user_stats['CRM_invoice'] = 0;
            $user_stats['CRM_dispute'] = 0;
            $user_stats['CRM_paid'] = 0;

            foreach ($quality_stats['send_cvs_from_cv_notes'] as $key => $cv) {
                $cv_cleared = History::where(['sub_stage' => 'quality_cleared', 'applicant_id' => $cv->applicant_id, 'sale_id' => $cv->sale_id, 'status' => 1])
                    ->whereBetween('updated_at', [$start_date, $end_date])->distinct()->first();

                if ($cv_cleared) {
                    $quality_stats['cvs_cleared']++;
                    /*** Sent CVs */
                    $user_stats['CRM_sent_cvs']++;

                    /*** Rejected CV */
                    $crm_rejected_cv = History::where(['sub_stage' => 'crm_reject', 'applicant_id' => $cv->applicant_id, 'sale_id' => $cv->sale_id, 'status' => 1])
                        ->whereBetween('created_at', [$start_date, $end_date])->first();
                    if ($crm_rejected_cv) {
                        $user_stats['CRM_rejected_cv']++;
                        continue;
                    }

                    /*** Request */
                    $crm_request = History::where(['sub_stage' => 'crm_request', 'applicant_id' => $cv->applicant_id, 'sale_id' => $cv->sale_id])
                        ->whereIn('id', function ($query) {
                            $query->select(DB::raw('MAX(id) FROM history h WHERE h.sub_stage="crm_request" and h.sale_id=history.sale_id and h.applicant_id=history.applicant_id'));
                        })->whereBetween('created_at', [$start_date, $end_date])->first();

                    $crm_sent_cv = CrmNote::where(['crm_notes.moved_tab_to' => 'cv_sent', 'crm_notes.applicant_id' => $cv->applicant_id, 'crm_notes.sale_id' => $cv->sale_id])
                        ->whereIn('crm_notes.id', function ($query) {
                            $query->select(DB::raw('MAX(id) FROM crm_notes as c WHERE c.moved_tab_to="cv_sent" and c.sale_id=crm_notes.sale_id and c.applicant_id=crm_notes.applicant_id'));
                        })->whereBetween('crm_notes.created_at', [$start_date, $end_date])->first();

                    if ($crm_request && $crm_sent_cv && (Carbon::parse($crm_request->history_added_date . ' ' . $crm_request->history_added_time)->gt($crm_sent_cv->created_at))) {
                        $user_stats['CRM_request']++;

                        /*** Rejected By Request */
                        $crm_rejected_by_request = History::where(['sub_stage' => 'crm_request_reject', 'applicant_id' => $cv->applicant_id, 'sale_id' => $cv->sale_id, 'status' => 1])
                            ->whereBetween('created_at', [$start_date, $end_date])->first();
                        if ($crm_rejected_by_request) {
                            $user_stats['CRM_rejected_by_request']++;
                            continue;
                        }

                        /*** Confirmation */
                        $crm_confirmation = History::where(['sub_stage' => 'crm_request_confirm', 'applicant_id' => $cv->applicant_id, 'sale_id' => $cv->sale_id])
                            ->whereIn('id', function ($query) {
                                $query->select(DB::raw('MAX(id) FROM history h WHERE h.sub_stage="crm_request_confirm" and h.sale_id=history.sale_id and h.applicant_id=history.applicant_id'));
                            })->whereBetween('created_at', [$start_date, $end_date])->first();

                        if ($crm_confirmation && (Carbon::parse($crm_confirmation->history_added_date . ' ' . $crm_confirmation->history_added_time)->gt(Carbon::parse($crm_request->history_added_date . ' ' . $crm_request->history_added_time)))) {
                            $user_stats['CRM_confirmation']++;

                            /*** Rebook */
                            $crm_rebook = History::where(['sub_stage' => 'crm_reebok', 'applicant_id' => $cv->applicant_id, 'sale_id' => $cv->sale_id, 'status' => 1])
                                ->whereBetween('created_at', [$start_date, $end_date])->first();
                            if ($crm_rebook) {
                                $user_stats['CRM_rebook']++;
                                continue;
                            }

                            /*** Attended Pre-Start Date */
                            $crm_attended = History::where(['sub_stage' => 'crm_interview_attended', 'applicant_id' => $cv->applicant_id, 'sale_id' => $cv->sale_id])
                                ->whereIn('id', function ($query) {
                                    $query->select(DB::raw('MAX(id) FROM history h WHERE h.sub_stage="crm_interview_attended" and h.sale_id=history.sale_id and h.applicant_id=history.applicant_id'));
                                })->whereBetween('created_at', [$start_date, $end_date])->first();
                            if ($crm_attended) {
                                $user_stats['CRM_attended']++;

                                /*** Declined */
                                $crm_declined = History::where(['sub_stage' => 'crm_declined', 'applicant_id' => $cv->applicant_id, 'sale_id' => $cv->sale_id, 'status' => 1])
                                    ->whereBetween('created_at', [$start_date, $end_date])->first();
                                if ($crm_declined) {
                                    $user_stats['CRM_declined']++;
                                    continue;
                                }

                                /*** Not Attended */
                                $crm_not_attended = History::where(['sub_stage' => 'crm_interview_not_attended', 'applicant_id' => $cv->applicant_id, 'sale_id' => $cv->sale_id, 'status' => 1])
                                    ->whereBetween('created_at', [$start_date, $end_date])->first();
                                if ($crm_not_attended) {
                                    $user_stats['CRM_not_attended']++;
                                    continue;
                                }

                                /*** Start Date */
                                $crm_start_date = History::where(['history.sub_stage' => 'crm_start_date', 'applicant_id' => $cv->applicant_id, 'sale_id' => $cv->sale_id])
                                    ->whereBetween('created_at', [$start_date, $end_date])->first();

                                $crm_start_date_back = History::where(['history.sub_stage' => 'crm_start_date_back', 'applicant_id' => $cv->applicant_id, 'sale_id' => $cv->sale_id])
                                    ->whereIn('id', function ($query) {
                                        $query->select(DB::raw('MAX(id) FROM history h WHERE h.sub_stage="crm_start_date_back" and h.sale_id=history.sale_id and h.applicant_id=history.applicant_id'));
                                    })->whereBetween('created_at', [$start_date, $end_date])
                                    ->first();

                                if (($crm_start_date && !$crm_start_date_back) || ($crm_start_date && $crm_start_date_back)) {

                                    $user_stats['CRM_start_date']++;
                                    $crm_start_date = $crm_start_date_back ? $crm_start_date_back : $crm_start_date;

                                    /*** Start Date Hold */
                                    $crm_start_date_hold = History::where([
                                        'history.sub_stage' => 'crm_start_date_hold', 
                                        'applicant_id' => $cv->applicant_id, 
                                        'sale_id' => $cv->sale_id, 
                                        'status' => 1
                                    ])
                                    ->whereBetween('created_at', [$start_date, $end_date])->first();

                                    if ($crm_start_date_hold) {
                                        $user_stats['CRM_start_date_hold']++;
                                        continue;
                                    }

                                    /*** Invoice */
                                    $crm_invoice = History::where(['history.sub_stage' => 'crm_invoice', 'applicant_id' => $cv->applicant_id, 'sale_id' => $cv->sale_id])
                                        ->whereIn('id', function ($query) {
                                            $query->select(DB::raw('MAX(id) FROM history h WHERE h.sub_stage="crm_invoice" and h.sale_id=history.sale_id and h.applicant_id=history.applicant_id'));
                                        })->whereBetween('created_at', [$start_date, $end_date])->first();

                                    if ($crm_invoice) {
                                        $user_stats['CRM_invoice']++;


                                        /*** Dispute */
                                        $crm_dispute = History::where(['sub_stage' => 'crm_dispute', 'applicant_id' => $cv->applicant_id, 'sale_id' => $cv->sale_id, 'status' => 1])
                                            ->whereBetween('created_at', [$start_date, $end_date])->first();

                                        if ($crm_dispute) {
                                            $user_stats['CRM_dispute']++;
                                            continue;
                                        }


                                        /*** Paid */
                                        $crm_paid = History::where(['sub_stage' => 'crm_paid', 'applicant_id' => $cv->applicant_id, 'sale_id' => $cv->sale_id])
                                            ->whereBetween('created_at', [$start_date, $end_date])->first();
                                        if ($crm_paid) {
                                            $user_stats['CRM_paid']++;
                                        }
                                    }
                                }
                            }
                        }
                    }
                } else {
                    $cv_rejected = History::where(['sub_stage' => 'quality_reject', 'applicant_id' => $cv->applicant_id, 'sale_id' => $cv->sale_id, 'status' => 1])
                        ->whereBetween('created_at', [$start_date, $end_date])->first();

                    if ($cv_rejected) {
                        $quality_stats['cvs_rejected']++;
                    }
                }
            }


            // ---------------------------------------------------Last month stats -------------------------------------------------------------

            if ($user_stats_updated) {
                foreach ($user_stats_updated as $key => $cv) {
                    /*** Start Date */
                    $crm_start_date = History::where([
                        'history.sub_stage' => 'crm_start_date', 
                        'applicant_id' => $cv->applicant_id, 
                        'sale_id' => $cv->sale_id])
                    ->whereBetween('created_at', [$start_date, $end_date])
                    ->first();

                    $crm_start_date_back = History::where([
                        'history.sub_stage' => 'crm_start_date_back', 
                        'applicant_id' => $cv->applicant_id, 
                        'sale_id' => $cv->sale_id
                    ])
                    ->whereIn('id', function ($query) {
                        $query->select(DB::raw('MAX(id) FROM history h WHERE h.sub_stage="crm_start_date_back" and h.sale_id=history.sale_id and h.applicant_id=history.applicant_id'));
                    })->whereBetween('created_at', [$start_date, $end_date])
                    ->first();

                    if (($crm_start_date && !$crm_start_date_back) || ($crm_start_date && $crm_start_date_back)) {
                        $prev_user_stats['CRM_start_date']++;
                        $crm_start_date = $crm_start_date_back ? $crm_start_date_back : $crm_start_date;
                    }
                    /*** Invoice */
                    $crm_invoice = History::where([
                        'history.sub_stage' => 'crm_invoice',
                        'applicant_id' => $cv->applicant_id,
                        'sale_id' => $cv->sale_id
                    ])
                    ->whereIn('id', function ($query) {
                        $query->select(DB::raw('MAX(id) FROM history h WHERE h.sub_stage="crm_invoice" and h.sale_id=history.sale_id and h.applicant_id=history.applicant_id'));
                    })
                    ->whereBetween('created_at', [$start_date, $end_date])
                    ->first();

                    if ($crm_invoice) {
                        $prev_user_stats['CRM_invoice']++;
                    }

                    /*** Paid */
                    $crm_paid = History::where(['sub_stage' => 'crm_paid', 'applicant_id' => $cv->applicant_id, 'sale_id' => $cv->sale_id])
                        ->whereBetween('created_at', [$start_date, $end_date])->first();
                    if ($crm_paid) {
                        $prev_user_stats['CRM_paid']++;
                    }

                }
            }
            unset($quality_stats['send_cvs_from_cv_notes']);
            unset($user_stats['all_send_cvs_from_cv_notes']);

            return response()->json([
                'quality_stats' => $quality_stats,
                'user_stats' => $user_stats,
                'prev_user_stats' => $prev_user_stats,
                'user_role' => $user_role,
                'user_name' => $user_name,
            ]);

        }
        return response()->json(['error' => $validator->errors()->all()]);
    }
    public function getWeeklySales()
    {
        $startOfWeek = now()->startOfWeek();
        $endOfWeek = now()->endOfWeek();

        $dailyCounts = Sale::whereBetween('created_at', [$startOfWeek, $endOfWeek])
            ->select(DB::raw('DAYOFWEEK(created_at) as day'), DB::raw('COUNT(*) as total'))
            ->groupBy(DB::raw('DAYOFWEEK(created_at)'))
            ->pluck('total', 'day');

        // Format: 1 = Sunday, 7 = Saturday
        $chartData = [];
        for ($i = 1; $i <= 7; $i++) {
            $chartData[] = $dailyCounts[$i] ?? 0;
        }

        $salesDetails = Sale::with(['office', 'unit'])
            ->whereBetween('created_at', [$startOfWeek, $endOfWeek])
            ->get(['id', 'unit_id', 'office_id', 'sale_postcode', 'created_at']);

        return response()->json([
            'total' => array_sum($chartData),
            'chartData' => $chartData,
            'details' => $salesDetails
        ]);
    }
    public function getSalesAnalytic(Request $request)
    {
        $range = $request->input('range', 'year');

        if ($range === 'year') {
            $from = now()->startOfYear();
            $to = now()->endOfYear();
            $grouping = 'MONTH(created_at)';
            $rangeLabels = collect(range(1, 12))->map(function ($month) {
                return Carbon::create()->month($month)->format('F');
            });
        } else {
            $from = now()->startOfMonth();
            $to = now()->endOfMonth();
            $grouping = 'DATE(created_at)';
            $daysInMonth = now()->daysInMonth;
            $rangeLabels = collect(range(1, $daysInMonth))->map(function ($day) {
                return now()->startOfMonth()->addDays($day - 1)->format('d M');
            });
        }

        $rawData = Sale::selectRaw("$grouping as label")
            ->selectRaw("SUM(CASE WHEN status = 1 AND created_at = updated_at THEN 1 ELSE 0 END) as new_added")
            ->selectRaw("SUM(CASE WHEN status = 1 AND is_re_open = 1 AND created_at != updated_at THEN 1 ELSE 0 END) as reopened")
            ->selectRaw("SUM(CASE WHEN status = 0 AND created_at != updated_at THEN 1 ELSE 0 END) as closed")
            ->whereBetween('created_at', [$from, $to])
            ->groupBy(DB::raw($grouping))
            ->orderBy(DB::raw($grouping))
            ->get()
            ->keyBy(function ($item) use ($range) {
                if ($range === 'year') {
                    return Carbon::create()->month((int)$item->label)->format('F');
                } else {
                    // $item->label is "YYYY-MM-DD"
                    return Carbon::parse($item->label)->format('d M');
                }
            });

        $labels = [];
        $new = [];
        $reopened = [];
        $closed = [];

        foreach ($rangeLabels as $label) {
            $labels[] = $label;
            $new[] = isset($rawData[$label]) ? (int) $rawData[$label]->new_added : 0;
            $reopened[] = isset($rawData[$label]) ? (int) $rawData[$label]->reopened : 0;
            $closed[] = isset($rawData[$label]) ? (int) $rawData[$label]->closed : 0;
        }

        return response()->json([
            'labels' => $labels,
            'new_added' => $new,
            'reopened' => $reopened,
            'closed' => $closed,
        ]);
    }





}
