<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;
use Horsefly\User;
use Horsefly\Sale;
use Horsefly\Office;
use Horsefly\Message;
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
use Illuminate\Support\Str;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function __construct()
    {
    }
    public function index()
    {
        return view('dashboards.index');
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
                    $path = asset('/images/users/user.png') ?? asset('/images/users/default.jpg');
                    return '<img src="'. $path .'" class="avatar-sm rounded-circle me-2" alt="kbp">' . $user->formatted_name; // Using accessor
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
        // Validate input using Laravel 12's Validator
        $validator = Validator::make($request->all(), [
            'user_key' => ['required', 'exists:users,id'],
            'date_range_filter' => ['required', 'regex:/^\d{4}-\d{2}-\d{2}\|\d{4}-\d{2}-\d{2}$/'],
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->all()], 422);
        }

        try {
            // Parse date range
            [$start_date, $end_date] = explode('|', $request->input('date_range_filter'));
            $start_date = trim($start_date) . ' 00:00:00';
            $end_date = trim($end_date) . ' 23:59:59';

            // Validate date formats using Carbon
            Carbon::parse($start_date);
            Carbon::parse($end_date);

            $user_id = $request->input('user_key');

            // Fetch user with role using Eloquent relationships
            $userWithRole = User::query()
                ->with(['roles' => fn ($query) => $query->select('roles.id', 'roles.name')])
                ->where('id', $user_id)
                ->select('id', 'name')
                ->firstOrFail();

            $user_role = $userWithRole->roles->first()->name ?? '';
            $user_name = $userWithRole->name ?? '';

            // Initialize stats arrays
            $quality_stats = [
                'cvs_sent' => 0,
                'cvs_rejected' => 0,
                'cvs_cleared' => 0,
            ];

            $user_stats = array_fill_keys([
                'CRM_sent_cvs', 'CRM_rejected_cv', 'CRM_request', 'CRM_rejected_by_request',
                'CRM_confirmation', 'CRM_rebook', 'CRM_attended', 'CRM_not_attended',
                'CRM_start_date', 'CRM_start_date_hold', 'CRM_declined', 'CRM_invoice',
                'CRM_dispute', 'CRM_paid', 'close_sales', 'open_sales', 'psl_offices',
                'non_psl_offices'
            ], 0);

            $prev_user_stats = array_fill_keys([
                'CRM_start_date', 'CRM_invoice', 'CRM_paid'
            ], 0);

            // Process sales-related data for Sales roles
            if (in_array($user_role, ['Sales', 'Sale and CRM'], true)) {
                // Fetch sales with related data
                $salesQuery = Sale::query()
                    ->where('user_id', $user_id)
                    ->whereIn('status', [0, 1])
                    ->whereBetween('created_at', [$start_date, $end_date]);

                // Count closed sales
                $user_stats['close_sales'] = Audit::query()
                    ->where('message', 'sale-closed')
                    ->where('auditable_type', Sale::class)
                    ->whereIn('auditable_id', $salesQuery->pluck('id'))
                    ->whereBetween('created_at', [$start_date, $end_date])
                    ->count();

                $sales = $salesQuery->get();
                $user_stats['open_sales'] = $sales->count() - $user_stats['close_sales'];

                // Count offices
                $offices = Office::query()
                    ->where('user_id', $user_id)
                    ->where('status', 1)
                    ->whereBetween('created_at', [$start_date, $end_date])
                    ->select('office_type')
                    ->get();

                $user_stats['psl_offices'] = $offices->where('office_type', 'psl')->count();
                $user_stats['non_psl_offices'] = $offices->where('office_type', 'non psl')->count();

                // Fetch CV notes for sales
                $cv_notes = CVNote::query()
                    ->whereIn('sale_id', $sales->pluck('id'))
                    ->whereBetween('updated_at', [$start_date, $end_date])
                    ->select('applicant_id', 'sale_id')
                    ->get();
            } else {
                // Fetch CV notes for non-sales roles
                $cv_notes = CVNote::query()
                    ->where('user_id', $user_id)
                    ->whereBetween('created_at', [$start_date, $end_date])
                    ->select('applicant_id', 'sale_id')
                    ->get();
            }

            $quality_stats['cvs_sent'] = $cv_notes->count();

            // Batch process CV-related stats
            $cv_grouped = $cv_notes->groupBy(['applicant_id', 'sale_id']);

            foreach ($cv_grouped as $applicant_id => $sales_group) {
                foreach ($sales_group as $sale_id => $cv_group) {
                    // Fetch all relevant history records
                    $history = History::query()
                        ->whereIn('sub_stage', [
                            'quality_cleared', 'quality_reject', 'crm_reject', 'crm_request',
                            'crm_request_confirm', 'crm_reebok', 'crm_interview_attended',
                            'crm_interview_not_attended', 'crm_start_date', 'crm_start_date_back',
                            'crm_start_date_hold', 'crm_invoice', 'crm_dispute', 'crm_paid',
                            'crm_request_reject', 'crm_declined'
                        ])
                        ->where('applicant_id', $applicant_id)
                        ->where('sale_id', $sale_id)
                        ->whereBetween('created_at', [$start_date, $end_date])
                        ->get()
                        ->keyBy('sub_stage');

                    // Quality stats
                    if (isset($history['quality_cleared']) && $history['quality_cleared']->status === 1) {
                        $quality_stats['cvs_cleared']++;
                        $user_stats['CRM_sent_cvs']++;
                    }
                    if (isset($history['quality_reject']) && $history['quality_reject']->status === 1) {
                        $quality_stats['cvs_rejected']++;
                    }
                    if (isset($history['crm_reject']) && $history['crm_reject']->status === 1) {
                        $user_stats['CRM_rejected_cv']++;
                        continue;
                    }

                    // CRM request and confirmation checks
                    if (isset($history['crm_request'])) {
                        $crm_sent_cv = CrmNote::query()
                            ->where([
                                'moved_tab_to' => 'cv_sent',
                                'applicant_id' => $applicant_id,
                                'sale_id' => $sale_id
                            ])
                            ->whereBetween('created_at', [$start_date, $end_date])
                            ->orderByDesc('id')
                            ->first();

                        if ($crm_sent_cv && Carbon::parse($history['crm_request']->history_added_date . ' ' . 
                            $history['crm_request']->history_added_time)->gt($crm_sent_cv->created_at)) {
                            $user_stats['CRM_request']++;
                            $this->processCrmStats($history, $user_stats, $applicant_id, $sale_id, $start_date, $end_date);
                        }
                    }
                }
            }

            // Previous month stats
            $prevMonthStart = Carbon::now()->subMonth()->startOfMonth()->format('Y-m-d') . ' 00:00:00';
            $prevMonthEnd = Carbon::now()->subMonth()->endOfMonth()->format('Y-m-d') . ' 23:59:59';

            $prev_cv_notes = CVNote::query()
                ->where('user_id', $user_id)
                ->whereBetween('created_at', [$prevMonthStart, $prevMonthEnd])
                ->select('applicant_id', 'sale_id')
                ->get();

            $prev_cv_grouped = $prev_cv_notes->groupBy(['applicant_id', 'sale_id']);

            foreach ($prev_cv_grouped as $applicant_id => $sales_group) {
                foreach ($sales_group as $sale_id => $cv_group) {
                    $prev_history = History::query()
                        ->whereIn('sub_stage', [
                            'crm_start_date', 'crm_start_date_back', 'crm_invoice', 'crm_paid'
                        ])
                        ->where('applicant_id', $applicant_id)
                        ->where('sale_id', $sale_id)
                        ->whereBetween('created_at', [$prevMonthStart, $prevMonthEnd])
                        ->get()
                        ->keyBy('sub_stage');

                    if (isset($prev_history['crm_start_date']) || isset($prev_history['crm_start_date_back'])) {
                        $prev_user_stats['CRM_start_date']++;
                    }
                    if (isset($prev_history['crm_invoice'])) {
                        $prev_user_stats['CRM_invoice']++;
                    }
                    if (isset($prev_history['crm_paid'])) {
                        $prev_user_stats['CRM_paid']++;
                    }
                }
            }

            return response()->json([
                'user_name' => $user_name,
                'user_role' => $user_role,
                'quality_stats' => $quality_stats,
                'user_stats' => $user_stats,
                'prev_user_stats' => $prev_user_stats,
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'An error occurred while processing statistics'], 500);
        }
    }
    private function processCrmStats($history, array &$user_stats, $applicant_id, $sale_id, string $start_date, string $end_date): void
    {
        if (isset($history['crm_request_reject']) && $history['crm_request_reject']->status === 1) {
            $user_stats['CRM_rejected_by_request']++;
        }

        if (isset($history['crm_request_confirm']) && isset($history['crm_request']) &&
            Carbon::parse($history['crm_request_confirm']->history_added_date . ' ' . 
                $history['crm_request_confirm']->history_added_time)->gt(
                Carbon::parse($history['crm_request']->history_added_date . ' ' . 
                    $history['crm_request']->history_added_time)
            )) {
            $user_stats['CRM_confirmation']++;

            if (isset($history['crm_reebok']) && $history['crm_reebok']->status === 1) {
                $user_stats['CRM_rebook']++;
            }

            if (isset($history['crm_interview_attended'])) {
                $user_stats['CRM_attended']++;

                if (isset($history['crm_declined']) && $history['crm_declined']->status === 1) {
                    $user_stats['CRM_declined']++;
                }

                if (isset($history['crm_interview_not_attended']) && $history['crm_interview_not_attended']->status === 1) {
                    $user_stats['CRM_not_attended']++;
                }

                if (isset($history['crm_start_date']) || isset($history['crm_start_date_back'])) {
                    $user_stats['CRM_start_date']++;

                    if (isset($history['crm_start_date_hold']) && $history['crm_start_date_hold']->status === 1) {
                        $user_stats['CRM_start_date_hold']++;
                    }

                    if (isset($history['crm_invoice'])) {
                        $user_stats['CRM_invoice']++;

                        if (isset($history['crm_dispute']) && $history['crm_dispute']->status === 1) {
                            $user_stats['CRM_dispute']++;
                        }

                        if (isset($history['crm_paid'])) {
                            $user_stats['CRM_paid']++;
                        }
                    }
                }
            }
        }
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
    public function getUnreadMessages()
    {
        try {
            $messages = Message::query()
                ->where('status', 'incoming')
                ->where('module_type', 'Horsefly\Applicant')
                ->where('is_read', 0)
                ->with(['user' => fn ($query) => $query->select('id', 'name')])
                ->select('id', 'user_id', 'message', 'created_at')
                ->latest()
                ->take(5)
                ->get()
                ->map(function ($message) {
                    return [
                        'id' => $message->id,
                        'user_name' => $message->applicant->applicant_name ?? 'Unknown',
                        'avatar' => asset('images/users/boy.png') ?? asset('images/users/default.jpg') ,
                        'message' => Str::limit(strip_tags($message->message), 150),
                        'created_at' => $message->created_at->diffForHumans(),
                    ];
                });

            $unreadCount = Message::where('status', 'incoming')
                ->where('module_type', 'Horsefly\Applicant')
                ->where('is_read', 0)
                ->count();

            return response()->json([
                'success' => true,
                'messages' => $messages,
                'unread_count' => $unreadCount,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to fetch messages: ' . $e->getMessage(),
            ], 500);
        }
    }


}
