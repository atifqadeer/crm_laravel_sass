<?php

namespace App\Exports;

use Horsefly\Applicant;
use Horsefly\Setting;
use Horsefly\JobSource;
use Horsefly\JobTitle;

use App\Traits\HasDistanceCalculation;
use App\Traits\FiltersRadiusApplicants;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class ApplicantsExport implements FromCollection, WithHeadings
{
    use HasDistanceCalculation;
    use FiltersRadiusApplicants;

    protected $type;
    protected $radius;
    protected $model_type;
    protected $model_id;
    protected $filters;

    public function __construct(
        string $type = 'all',
        ?float $radius = null,
        ?string $model_type = null,
        ?int $model_id = null,
        array $filters = []
    ) {
        $this->type = $type;
        $this->radius = $radius;
        $this->model_type = $model_type;
        $this->model_id = $model_id;
        $this->filters = $filters;
    }

    public function collection()
    {
        $rows = [];
        foreach ($this->exportCursor() as $item) {
            $rows[] = $this->mapRow($item);
        }

        return collect($rows);
    }

    public function headings(): array
    {
        switch ($this->type) {
            case 'emails':
                return ['Created At', 'Applicant Name', 'Email (Primary)', 'Email (Secondary)', 'Job Category', 'Job Type', 'Job Title'];
            case 'noLatLong':
                return ['Created At', 'Applicant Name', 'Postcode', 'Latitude', 'Longitude', 'Job Category', 'Job Type', 'Job Title'];
            case 'all':
                return ['Created At', 'Applicant Name', 'Email (Primary)', 'Email (Secondary)', 'Postcode', 'Phone (Primary)', 'Phone (Secondary)', 'Landline', 'Job Category', 'Job Type', 'Job Title', 'Experience', 'Notes'];
            case 'withinRadius':
                return ['Date', 'Applicant Name', 'Email (Primary)', 'Email (Secondary)', 'Job Title', 'Job Category', 'Job Type', 'Postcode', 'Phone (Primary)', 'Phone (Secondary)', 'Landline', 'Experience', 'Job Source', 'Nursing Home Experience', 'Notes', 'Applicant Status', 'CV Status'];
            case 'allRejected':
                return ['Date', 'Applicant Name', 'Email (Primary)', 'Email (Secondary)', 'Postcode', 'Phone (Primary)', 'Phone (Secondary)', 'Landline', 'Job Category', 'Job Type', 'Job Title', 'Job Source', 'Rejection Type', 'Experience', 'Notes'];
            case 'allBlocked':
                return ['Date', 'Applicant Name', 'Email (Primary)', 'Email (Secondary)', 'Postcode', 'Phone (Primary)', 'Phone (Secondary)', 'Landline', 'Job Category', 'Job Type', 'Job Title', 'Job Source', 'Status', 'Experience', 'Notes'];
            case 'allPaid':
                return ['Date', 'Applicant Name', 'Email (Primary)', 'Email (Secondary)', 'Postcode', 'Phone (Primary)', 'Phone (Secondary)', 'Landline', 'Job Category', 'Job Type', 'Job Title', 'Job Source', 'Status', 'Experience', 'Notes'];
            case 'allNoJob':
                return ['Date', 'Agent', 'Applicant Name', 'Email (Primary)', 'Email (Secondary)', 'Postcode', 'Phone (Primary)', 'Phone (Secondary)', 'Landline', 'Job Category', 'Job Type', 'Job Title', 'Job Source', 'Experience', 'Notes'];
            default:
                return [];
        }
    }

    public function streamCsv($handle): void
    {
        set_time_limit(0);
        ignore_user_abort(true);
        DB::disableQueryLog();

        fwrite($handle, "\xEF\xBB\xBF");
        fputcsv($handle, $this->headings());

        foreach ($this->exportCursor() as $item) {
            $row = $this->mapRow($item);
            foreach ($row as $index => $value) {
                if ($value === null) {
                    $row[$index] = '';
                }
            }
            fputcsv($handle, $row);
        }
    }

    public function streamWithinRadiusCsv($handle): void
    {
        $this->streamCsv($handle);
    }

    private function exportCursor()
    {
        $query = $this->buildQuery($this->hiddenJobSourceIds());

        if ($query === null) {
            return [];
        }

        return $query->toBase()->cursor();
    }

    private function hiddenJobSourceIds(): array
    {
        $hidePrivateDataSetting = Setting::where('key', 'hide_private_data')->value('value');
        $hidePrivateData = array_filter(
            array_map('trim', explode(',', $hidePrivateDataSetting ?? ''))
        );

        if (Gate::allows('show-private-data') || count($hidePrivateData) === 0) {
            return [];
        }

        return JobSource::where('is_active', 1)
            ->where(function ($q) use ($hidePrivateData) {
                foreach ($hidePrivateData as $hideName) {
                    $q->orWhere('name', 'LIKE', '%' . $hideName . '%');
                }
            })
            ->pluck('id')
            ->all();
    }

    private function applyHiddenSources($query, array $sourceIds)
    {
        if ($sourceIds === []) {
            return $query;
        }

        return $query->where(function ($q) use ($sourceIds) {
            $q->whereNotIn('applicants.job_source_id', $sourceIds)
                ->orWhereNull('applicants.job_source_id');
        });
    }

    private function applyApplicantListFilters($query, bool $applyStatus = true)
    {
        if ($applyStatus) {
            $statusFilter = strtolower(trim(preg_replace('/\s+/', ' ', (string) ($this->filters['status_filter'] ?? ''))));
            if (in_array($statusFilter, ['', 'all'], true)) {
                $query->where('applicants.status', 1);
            } else {
                switch ($statusFilter) {
                    case 'crm active':
                        $query->where(function ($q) {
                            $q->where('applicants.is_cv_in_quality_clear', 1)
                                ->orWhere('applicants.is_interview_confirm', 1)
                                ->orWhere('applicants.is_interview_attend', 1)
                                ->orWhere('applicants.is_in_crm_request', 1)
                                ->orWhere('applicants.is_crm_request_confirm', 1)
                                ->orWhere('applicants.is_crm_interview_attended', '<>', 0)
                                ->orWhere('applicants.is_in_crm_start_date', 1)
                                ->orWhere('applicants.is_in_crm_invoice', 1)
                                ->orWhere('applicants.is_in_crm_invoice_sent', 1)
                                ->orWhere('applicants.is_in_crm_start_date_hold', 1)
                                ->orWhere('applicants.is_in_crm_paid', 1);
                        })
                            ->where('applicants.is_blocked', false)
                            ->whereExists(function ($sub) {
                                $sub->select(DB::raw(1))
                                    ->from('history')
                                    ->whereRaw('history.applicant_id = applicants.id')
                                    ->where('history.stage', 'crm')
                                    ->limit(1);
                            });
                        break;

                    case 'blocked':
                        $query->where('applicants.is_blocked', true)
                            ->where('applicants.is_no_job', false)
                            ->where('applicants.is_circuit_busy', false)
                            ->where('applicants.is_temp_not_interested', false);
                        break;

                    case 'circuit busy':
                        $query->where('applicants.is_blocked', false)
                            ->where('applicants.is_no_job', false)
                            ->where('applicants.is_circuit_busy', true)
                            ->where('applicants.is_temp_not_interested', false);
                        break;

                    case 'not interested':
                        $query->where('applicants.is_no_job', false)
                            ->where('applicants.is_blocked', false)
                            ->where('applicants.is_circuit_busy', false)
                            ->where('applicants.is_temp_not_interested', true);
                        break;

                    case 'no job':
                        $query->where('applicants.is_blocked', false)
                            ->where('applicants.is_circuit_busy', false)
                            ->where('applicants.is_no_job', true)
                            ->where('applicants.is_temp_not_interested', false);
                        break;

                    default:
                        $query->where('applicants.status', 1);
                        break;
                }
            }
        }

        $typeFilter = strtolower(trim((string) ($this->filters['type_filter'] ?? '')));
        if (in_array($typeFilter, ['specialist', 'regular'], true)) {
            $query->where('applicants.job_type', $typeFilter);
        }

        $categoryIds = $this->arrayFilterIds($this->filters['category_filter'] ?? null);
        if ($categoryIds !== []) {
            $query->whereIn('applicants.job_category_id', $categoryIds);
        }

        $titleIds = $this->arrayFilterIds($this->filters['title_filters'] ?? $this->filters['title_filter'] ?? null);
        if ($titleIds !== []) {
            $query->whereIn('applicants.job_title_id', $titleIds);
        }

        $sourceIds = $this->arrayFilterIds($this->filters['source_filter'] ?? null);
        if ($sourceIds !== []) {
            $query->whereIn('applicants.job_source_id', $sourceIds);
        }

        $search = trim((string) ($this->filters['search'] ?? ''));
        if ($search !== '') {
            $query->where(function ($inner) use ($search) {
                $inner->where('applicants.applicant_name', 'LIKE', "%{$search}%")
                    ->orWhere('applicants.applicant_email', 'LIKE', "%{$search}%")
                    ->orWhere('applicants.applicant_email_secondary', 'LIKE', "%{$search}%")
                    ->orWhere('applicants.applicant_postcode', 'LIKE', "%{$search}%")
                    ->orWhere('applicants.applicant_phone', 'LIKE', "%{$search}%")
                    ->orWhere('applicants.applicant_phone_secondary', 'LIKE', "%{$search}%")
                    ->orWhere('applicants.applicant_landline', 'LIKE', "%{$search}%")
                    ->orWhere('job_titles.name', 'LIKE', "%{$search}%")
                    ->orWhere('job_categories.name', 'LIKE', "%{$search}%");
            });
        }

        return $query;
    }

    private function latestRowsSubquery(string $table, string $groupColumn, ?string $typeColumn = null, ?string $typeValue = null)
    {
        $latestIds = DB::table($table)->selectRaw('MAX(id)');
        if ($typeColumn !== null) {
            $latestIds->where($typeColumn, $typeValue);
        }
        $latestIds->groupBy($groupColumn);

        $query = DB::table($table)->whereIn('id', $latestIds);
        if ($typeColumn !== null) {
            $query->where($typeColumn, $typeValue);
        }

        return $query;
    }

    private function buildQuery(array $sourceIds)
    {
        switch ($this->type) {
            case 'emails':
                $emailsQuery = $this->applyHiddenSources(
                    Applicant::query()
                        ->select([
                            'applicants.id',
                            'applicants.applicant_name',
                            'applicants.applicant_email',
                            'applicants.applicant_email_secondary',
                            'applicants.job_type',
                            'applicants.created_at',
                            'job_categories.name as job_category',
                            'job_titles.name as job_title',
                        ])
                        ->leftJoin('job_categories', 'applicants.job_category_id', '=', 'job_categories.id')
                        ->leftJoin('job_titles', 'applicants.job_title_id', '=', 'job_titles.id')
                        ->whereNull('applicants.deleted_at'),
                    $sourceIds
                );

                return $this->applyApplicantListFilters($emailsQuery);

            case 'noLatLong':
                $noLatLongQuery = $this->applyHiddenSources(
                    Applicant::query()
                        ->select([
                            'applicants.id',
                            'applicants.applicant_name',
                            'applicants.applicant_postcode',
                            'applicants.lat',
                            'applicants.lng',
                            'job_categories.name as job_category',
                            'applicants.job_type',
                            'job_titles.name as job_title',
                            'applicants.created_at',
                        ])
                        ->leftJoin('job_categories', 'applicants.job_category_id', '=', 'job_categories.id')
                        ->leftJoin('job_titles', 'applicants.job_title_id', '=', 'job_titles.id')
                        ->where(function ($q) {
                            $q->whereNull('applicants.lat')
                                ->orWhere('applicants.lat', '')
                                ->orWhere('applicants.lat', '0')
                                ->orWhere('applicants.lat', 0);
                        })
                        ->where(function ($q) {
                            $q->whereNull('applicants.lng')
                                ->orWhere('applicants.lng', '')
                                ->orWhere('applicants.lng', '0')
                                ->orWhere('applicants.lng', 0);
                        })
                        ->whereNull('applicants.deleted_at'),
                    $sourceIds
                );

                return $this->applyApplicantListFilters($noLatLongQuery);

            case 'all':
                $latestNotes = $this->latestRowsSubquery('applicant_notes', 'applicant_id')
                    ->select('applicant_id', 'details');

                $allQuery = $this->applyHiddenSources(
                    Applicant::query()
                        ->select([
                            'applicants.id',
                            'applicants.applicant_name',
                            'applicants.applicant_email',
                            'applicants.applicant_email_secondary',
                            'applicants.applicant_postcode',
                            'applicants.applicant_phone',
                            'applicants.applicant_phone_secondary',
                            'applicants.applicant_landline',
                            'applicants.applicant_experience',
                            'applicants.applicant_notes',
                            'latest_applicant_notes.details as note_details',
                            'applicants.created_at',
                            'job_categories.name as job_category',
                            'applicants.job_type',
                            'job_titles.name as job_title',
                        ])
                        ->leftJoin('job_categories', 'applicants.job_category_id', '=', 'job_categories.id')
                        ->leftJoin('job_titles', 'applicants.job_title_id', '=', 'job_titles.id')
                        ->leftJoinSub($latestNotes, 'latest_applicant_notes', function ($join) {
                            $join->on('applicants.id', '=', 'latest_applicant_notes.applicant_id');
                        })
                        ->whereNull('applicants.deleted_at'),
                    $sourceIds
                );

                return $this->applyApplicantListFilters($allQuery);

            case 'withinRadius':
                return $this->buildWithinRadiusQuery($sourceIds);

            case 'allRejected':
                return $this->applyApplicantListFilters($this->buildAllRejectedQuery($sourceIds), false);

            case 'allBlocked':
                $blockedQuery = $this->applyHiddenSources(
                    Applicant::query()
                        ->select([
                            'applicants.id',
                            'applicants.updated_at',
                            'applicants.applicant_name',
                            'applicants.applicant_email',
                            'applicants.applicant_email_secondary',
                            'applicants.applicant_postcode',
                            'applicants.applicant_phone',
                            'applicants.applicant_phone_secondary',
                            'applicants.applicant_landline',
                            'job_categories.name as job_category',
                            'applicants.job_type as job_type',
                            'job_titles.name as job_title',
                            'job_sources.name as job_source',
                            'applicants.applicant_experience',
                            'applicants.applicant_notes',
                        ])
                        ->leftJoin('job_categories', 'applicants.job_category_id', '=', 'job_categories.id')
                        ->leftJoin('job_titles', 'applicants.job_title_id', '=', 'job_titles.id')
                        ->leftJoin('job_sources', 'applicants.job_source_id', '=', 'job_sources.id')
                        ->whereNotExists(function ($q) {
                            $q->select(DB::raw(1))
                                ->from('applicants_pivot_sales')
                                ->whereColumn('applicants_pivot_sales.applicant_id', 'applicants.id');
                        })
                        ->where('applicants.status', 1)
                        ->where('applicants.is_blocked', 1)
                        ->whereNull('applicants.deleted_at'),
                    $sourceIds
                );

                return $this->applyApplicantListFilters($blockedQuery, false);

            case 'allPaid':
                $paidTabs = ['paid', 'dispute', 'start_date_hold', 'declined', 'start_date'];
                $latestCrmIds = DB::table('crm_notes')
                    ->selectRaw('MAX(id)')
                    ->whereIn('moved_tab_to', $paidTabs)
                    ->groupBy('applicant_id');
                $latestCrm = DB::table('crm_notes')
                    ->select('applicant_id', 'details', 'created_at', 'moved_tab_to')
                    ->whereIn('moved_tab_to', $paidTabs)
                    ->whereIn('id', $latestCrmIds);

                $paidQuery = $this->applyHiddenSources(
                    Applicant::query()
                        ->select([
                            'applicants.id',
                            'crm_notes.created_at as crm_notes_created',
                            'applicants.applicant_name',
                            'applicants.applicant_email',
                            'applicants.applicant_email_secondary',
                            'applicants.applicant_postcode',
                            'applicants.applicant_phone',
                            'applicants.applicant_phone_secondary',
                            'applicants.applicant_landline',
                            'job_categories.name as job_category',
                            'applicants.job_type as job_type',
                            'job_titles.name as job_title',
                            'job_sources.name as job_source',
                            'crm_notes.moved_tab_to',
                            'crm_notes.details',
                            'applicants.applicant_experience',
                        ])
                        ->joinSub($latestCrm, 'crm_notes', function ($join) {
                            $join->on('applicants.id', '=', 'crm_notes.applicant_id');
                        })
                        ->leftJoin('job_categories', 'applicants.job_category_id', '=', 'job_categories.id')
                        ->leftJoin('job_titles', 'applicants.job_title_id', '=', 'job_titles.id')
                        ->leftJoin('job_sources', 'applicants.job_source_id', '=', 'job_sources.id')
                        ->where('applicants.is_no_job', 0)
                        ->where('applicants.status', 1)
                        ->where('applicants.is_blocked', 0)
                        ->whereNull('applicants.deleted_at')
                        ->whereIn('applicants.paid_status', ['open', 'pending'])
                        ->whereIn('crm_notes.moved_tab_to', ['paid', 'dispute', 'start_date_hold', 'declined', 'start_date']),
                    $sourceIds
                );

                return $this->applyApplicantListFilters($paidQuery, false);

            case 'allNoJob':
                $latestNotes = $this->latestRowsSubquery('module_notes', 'module_noteable_id', 'module_noteable_type', Applicant::class)
                    ->select([
                        'module_noteable_id',
                        'user_id',
                        'details',
                        'created_at',
                    ]);

                $noJobQuery = $this->applyHiddenSources(
                    Applicant::query()
                        ->select([
                            'applicants.id',
                            'applicants.applicant_name',
                            'applicants.applicant_email',
                            'applicants.applicant_email_secondary',
                            'applicants.applicant_postcode',
                            'applicants.applicant_phone',
                            'applicants.applicant_phone_secondary',
                            'applicants.applicant_landline',
                            'applicants.job_type',
                            'applicants.applicant_experience',
                            'job_titles.name as job_title_name',
                            'job_categories.name as job_category_name',
                            'job_sources.name as job_source_name',
                            'users.name as user_name',
                            'module_notes.details as module_notes_details',
                            'module_notes.created_at as note_created_at',
                        ])
                        ->joinSub($latestNotes, 'module_notes', function ($join) {
                            $join->on('applicants.id', '=', 'module_notes.module_noteable_id');
                        })
                        ->leftJoin('users', 'module_notes.user_id', '=', 'users.id')
                        ->leftJoin('job_titles', 'applicants.job_title_id', '=', 'job_titles.id')
                        ->leftJoin('job_categories', 'applicants.job_category_id', '=', 'job_categories.id')
                        ->leftJoin('job_sources', 'applicants.job_source_id', '=', 'job_sources.id')
                        ->where('applicants.is_no_job', 1)
                        ->where('applicants.status', 1)
                        ->where('applicants.is_blocked', 0)
                        ->whereNull('applicants.deleted_at'),
                    $sourceIds
                );

                return $this->applyApplicantListFilters($noJobQuery, false);

            default:
                return null;
        }
    }

    private function buildAllRejectedQuery(array $sourceIds)
    {
        $radius = 15;

        $latestNotes = DB::table('crm_notes')
            ->select('id', 'applicant_id', 'sale_id', 'details', 'created_at', 'moved_tab_to')
            ->whereIn('id', function ($q) {
                $q->select(DB::raw('MAX(id)'))
                    ->from('crm_notes')
                    ->groupBy('applicant_id', 'sale_id');
            });

        $latestHistory = DB::table('history')
            ->select('id', 'applicant_id', 'sale_id', 'sub_stage', 'status')
            ->whereIn('id', function ($q) {
                $q->select(DB::raw('MAX(id)'))
                    ->from('history')
                    ->groupBy('applicant_id', 'sale_id');
            });

        $query = Applicant::query()
            ->select([
                'applicants.id',
                'crm_notes.created_at as crm_notes_created',
                'applicants.applicant_name',
                'applicants.applicant_email',
                'applicants.applicant_email_secondary',
                'applicants.applicant_postcode',
                'applicants.applicant_phone',
                'applicants.applicant_phone_secondary',
                'applicants.applicant_landline',
                'job_categories.name as job_category',
                'applicants.job_type as job_type',
                'job_titles.name as job_title',
                'job_sources.name as job_source',
                'applicants.applicant_experience',
                'crm_notes.details',
                DB::raw('CASE
                    WHEN history.sub_stage = "crm_reject" THEN "Rejected CV"
                    WHEN history.sub_stage = "crm_request_reject" THEN "Rejected By Request"
                    WHEN history.sub_stage = "crm_interview_not_attended" THEN "Not Attended"
                    WHEN history.sub_stage IN ("crm_start_date_hold", "crm_start_date_hold_save") THEN "Start Date Hold"
                    ELSE "Unknown Status"
                END AS sub_stage'),
            ])
            ->joinSub($latestNotes, 'crm_notes', function ($join) {
                $join->on('applicants.id', '=', 'crm_notes.applicant_id');
            })
            ->joinSub($latestHistory, 'history', function ($join) {
                $join->on('applicants.id', '=', 'history.applicant_id')
                    ->on('crm_notes.sale_id', '=', 'history.sale_id');
            })
            ->leftJoin('job_titles', 'applicants.job_title_id', '=', 'job_titles.id')
            ->leftJoin('job_categories', 'applicants.job_category_id', '=', 'job_categories.id')
            ->leftJoin('job_sources', 'applicants.job_source_id', '=', 'job_sources.id')
            ->whereIn('history.sub_stage', [
                'crm_interview_not_attended',
                'crm_request_reject',
                'crm_reject',
                'crm_start_date_hold',
                'crm_start_date_hold_save',
            ])
            ->whereIn('crm_notes.moved_tab_to', [
                'interview_not_attended',
                'request_reject',
                'cv_sent_reject',
                'start_date_hold',
                'start_date_hold_save',
            ])
            ->where([
                'applicants.status' => 1,
                'history.status' => 1,
                'applicants.is_in_nurse_home' => 0,
                'applicants.is_blocked' => 0,
                'applicants.is_callback_enable' => 0,
                'applicants.is_no_job' => 0,
            ])
            ->whereNull('applicants.deleted_at')
            ->whereExists(function ($sub) use ($sourceIds, $radius) {
                $sub->select(DB::raw(1))
                    ->from('sales')
                    ->where('sales.status', 1)
                    ->where('sales.is_on_hold', 0)
                    ->whereNotNull('sales.lat')
                    ->whereNotNull('sales.lng')
                    ->where(function ($inner) use ($radius) {
                        $latDelta = $radius / 111.32;
                        $lngDelta = $radius / 70;
                        $inner->whereColumn('sales.sale_postcode', 'applicants.applicant_postcode')
                            ->orWhereRaw(
                                'ABS(applicants.lat - sales.lat) <= ?
                                 AND ABS(applicants.lng - sales.lng) <= ?
                                 AND (6371 * ACOS(LEAST(1, GREATEST(-1,
                                    COS(RADIANS(sales.lat)) * COS(RADIANS(applicants.lat)) *
                                    COS(RADIANS(applicants.lng) - RADIANS(sales.lng)) +
                                    SIN(RADIANS(sales.lat)) * SIN(RADIANS(applicants.lat))
                                 )))) <= ?',
                                [$latDelta, $lngDelta, $radius]
                            );
                    });

                if (count($sourceIds) > 0) {
                    $sub->where(function ($q) use ($sourceIds) {
                        $q->whereNotIn('sales.job_source_id', $sourceIds)
                            ->orWhereNull('sales.job_source_id');
                    });
                }
            });

        return $this->applyHiddenSources($query, $sourceIds);
    }

    private function buildWithinRadiusQuery(array $sourceIds)
    {
        $sale = $this->model_type::find($this->model_id);
        $lat = (float) $sale->lat;
        $lng = (float) $sale->lng;
        $saleId = (int) $this->model_id;
        $radius = (float) ($this->radius ?? 15);

        if (empty($this->filters['category_id']) && !empty($sale->job_category_id)) {
            $this->filters['category_id'] = $sale->job_category_id;
        }

        // --- Resolve job_title_id + related title ids ---
        $titleIds = collect([$sale->job_title_id]);

        if ($sale->jobTitle && !empty($sale->jobTitle->related_titles)) {
            $raw = $sale->jobTitle->related_titles;

            if (is_array($raw)) {
                $relatedNames = $raw;
            } else {
                $decoded = json_decode($raw, true);
                $relatedNames = is_array($decoded)
                    ? $decoded
                    : array_filter(array_map('trim', explode(',', $raw)));
            }

            if (!empty($relatedNames)) {
                $relatedIds = JobTitle::whereIn(
                    DB::raw('LOWER(TRIM(name))'),
                    array_map(fn($n) => strtolower(trim($n)), $relatedNames)
                )->pluck('id');

                $titleIds = $titleIds->merge($relatedIds);
            }
        }

        $titleIds = $titleIds->filter()->map(fn($id) => (int) $id)->unique()->values()->all();


        $latestModuleNotes = $this->latestRowsSubquery('module_notes', 'module_noteable_id', 'module_noteable_type', Applicant::class)
            ->select('module_noteable_id', 'details', 'created_at');

        $latestApplicantNotes = $this->latestRowsSubquery('applicant_notes', 'applicant_id')
            ->select('applicant_id', 'details', 'created_at');

        $cvForSale = DB::table('cv_notes')
            ->select([
                'applicant_id',
                DB::raw('MAX(CASE WHEN status = 1 THEN 1 ELSE 0 END) as has_sent'),
                DB::raw('MAX(CASE WHEN status = 2 THEN 1 ELSE 0 END) as has_paid'),
                DB::raw('MAX(CASE WHEN status = 0 THEN 1 ELSE 0 END) as has_reject'),
            ])
            ->where('sale_id', $saleId)
            ->groupBy('applicant_id');

        $cvOtherSent = DB::table('cv_notes')
            ->select('applicant_id')
            ->where('sale_id', '!=', $saleId)
            ->where('status', 1)
            ->groupBy('applicant_id');

        $noJobHistory = DB::table('history')
            ->select('applicant_id')
            ->where('sale_id', $saleId)
            ->whereIn('sub_stage', ['quality_cleared_no_job', 'crm_no_job_request'])
            ->groupBy('applicant_id');

        $pivotForSale = DB::table('applicants_pivot_sales')
            ->select('applicant_id')
            ->where('sale_id', $saleId)
            ->groupBy('applicant_id');

        $query = Applicant::query()
            ->select([
                'applicants.id',
                'applicants.applicant_name',
                'applicants.applicant_email',
                'applicants.applicant_email_secondary',
                'applicants.applicant_postcode',
                'applicants.applicant_phone',
                'applicants.applicant_phone_secondary',
                'applicants.applicant_landline',
                'applicants.applicant_experience',
                'applicants.job_type',
                'applicants.have_nursing_home_experience',
                'job_titles.name as job_title_name',
                'job_categories.name as job_category_name',
                'job_sources.name as job_source_name',
                DB::raw('COALESCE(latest_module_notes.details, latest_applicant_notes.details) AS notes_details'),
                DB::raw('COALESCE(latest_module_notes.created_at, latest_applicant_notes.created_at, applicants.updated_at) AS notes_created_at'),
                DB::raw("CASE
                    WHEN applicants.have_nursing_home_experience = 1 THEN 'Have Nursing Home Experience'
                    WHEN applicants.is_blocked = 1 THEN 'Blocked'
                    WHEN applicants.is_no_job = 1 OR no_job_history.applicant_id IS NOT NULL THEN 'No Job'
                    WHEN applicants.is_callback_enable = 1 THEN 'Callback'
                    WHEN applicants.is_temp_not_interested = 1 OR pivot_for_sale.applicant_id IS NOT NULL THEN 'Not Interested'
                    ELSE 'Interested'
                END AS applicant_status"),
                DB::raw("CASE
                    WHEN applicants.paid_status = 'close' THEN 'Paid'
                    WHEN cv_for_sale.has_sent = 1 THEN 'Sent'
                    WHEN cv_for_sale.has_paid = 1 THEN 'Paid'
                    WHEN cv_for_sale.has_reject = 1 THEN 'Reject Job'
                    WHEN cv_other_sent.applicant_id IS NOT NULL THEN 'CRM Active'
                    ELSE 'Open'
                END AS cv_status"),
            ])
            ->leftJoin('job_titles', 'applicants.job_title_id', '=', 'job_titles.id')
            ->leftJoin('job_categories', 'applicants.job_category_id', '=', 'job_categories.id')
            ->leftJoin('job_sources', 'applicants.job_source_id', '=', 'job_sources.id')
            ->leftJoinSub($latestModuleNotes, 'latest_module_notes', function ($join) {
                $join->on('applicants.id', '=', 'latest_module_notes.module_noteable_id');
            })
            ->leftJoinSub($latestApplicantNotes, 'latest_applicant_notes', function ($join) {
                $join->on('applicants.id', '=', 'latest_applicant_notes.applicant_id');
            })
            ->leftJoinSub($cvForSale, 'cv_for_sale', function ($join) {
                $join->on('applicants.id', '=', 'cv_for_sale.applicant_id');
            })
            ->leftJoinSub($cvOtherSent, 'cv_other_sent', function ($join) {
                $join->on('applicants.id', '=', 'cv_other_sent.applicant_id');
            })
            ->leftJoinSub($noJobHistory, 'no_job_history', function ($join) {
                $join->on('applicants.id', '=', 'no_job_history.applicant_id');
            })
            ->leftJoinSub($pivotForSale, 'pivot_for_sale', function ($join) {
                $join->on('applicants.id', '=', 'pivot_for_sale.applicant_id');
            })
            ->where('applicants.status', 1)
            ->whereNull('applicants.deleted_at')
            ->where('applicants.is_in_nurse_home', 0)
            ->whereNotNull('applicants.lat')
            ->whereNotNull('applicants.lng')
            ->whereIn('applicants.job_title_id', $titleIds);

        $this->applyRadiusDistanceFilter($query, $lat, $lng, $radius);
        $this->applyHiddenSources($query, $sourceIds);
        $this->applyRadiusApplicantFilters($query, $saleId, $this->filters);

        return $query->orderByRaw('notes_created_at DESC');
    }

    private function mapRow(object $item): array
    {
        switch ($this->type) {
            case 'emails':
                return [
                    $this->formatDate($item->created_at),
                    $this->titleCase($item->applicant_name),
                    $item->applicant_email,
                    $item->applicant_email_secondary,
                    strtoupper((string) $item->job_category),
                    strtoupper((string) $item->job_type),
                    strtoupper((string) $item->job_title),
                ];

            case 'noLatLong':
                return [
                    $this->formatDate($item->created_at),
                    $this->titleCase($item->applicant_name),
                    strtoupper((string) $item->applicant_postcode),
                    $item->lat,
                    $item->lng,
                    strtoupper((string) $item->job_category),
                    strtoupper((string) $item->job_type),
                    strtoupper((string) $item->job_title),
                ];

            case 'all':
                return [
                    $this->formatDate($item->created_at),
                    $this->titleCase($item->applicant_name),
                    $item->applicant_email,
                    $item->applicant_email_secondary,
                    strtoupper((string) $item->applicant_postcode),
                    $item->applicant_phone,
                    $item->applicant_phone_secondary,
                    $item->applicant_landline,
                    strtoupper((string) $item->job_category),
                    strtoupper((string) $item->job_type),
                    strtoupper((string) $item->job_title),
                    $item->applicant_experience,
                    $item->note_details ?? $item->applicant_notes,
                ];

            case 'withinRadius':
                $notes = preg_replace('/\s+/u', ' ', strip_tags((string) ($item->notes_details ?? ''))) ?? '';

                return [
                    $this->formatDate($item->notes_created_at),
                    $this->titleCase($item->applicant_name),
                    $item->applicant_email,
                    $item->applicant_email_secondary,
                    strtoupper((string) $item->job_title_name),
                    strtoupper((string) $item->job_category_name),
                    strtoupper((string) $item->job_type),
                    strtoupper((string) $item->applicant_postcode),
                    $item->applicant_phone,
                    $item->applicant_phone_secondary,
                    $item->applicant_landline,
                    $item->applicant_experience,
                    $item->job_source_name ? strtoupper($item->job_source_name) : '',
                    $item->have_nursing_home_experience == 1
                        ? 'Yes'
                        : ($item->have_nursing_home_experience == 0 ? 'No' : 'NULL'),
                    $notes,
                    $item->applicant_status,
                    $item->cv_status,
                ];

            case 'allRejected':
                return [
                    $this->formatDate($item->crm_notes_created),
                    $this->titleCase($item->applicant_name),
                    $item->applicant_email,
                    $item->applicant_email_secondary,
                    strtoupper((string) $item->applicant_postcode),
                    $item->applicant_phone,
                    $item->applicant_phone_secondary,
                    $item->applicant_landline,
                    ucwords((string) $item->job_category),
                    ucwords((string) $item->job_type),
                    strtoupper((string) $item->job_title),
                    ucwords((string) $item->job_source),
                    ucwords((string) $item->sub_stage),
                    $item->applicant_experience,
                    $item->details,
                ];

            case 'allBlocked':
                return [
                    $this->formatDate($item->updated_at),
                    $this->titleCase($item->applicant_name),
                    $item->applicant_email,
                    $item->applicant_email_secondary,
                    strtoupper((string) $item->applicant_postcode),
                    $item->applicant_phone,
                    $item->applicant_phone_secondary,
                    $item->applicant_landline,
                    strtoupper((string) $item->job_category),
                    strtoupper((string) $item->job_type),
                    strtoupper((string) $item->job_title),
                    strtoupper((string) $item->job_source),
                    'Blocked',
                    $item->applicant_experience,
                    $item->applicant_notes ?? 'N/A',
                ];

            case 'allPaid':
                return [
                    $this->formatDate($item->crm_notes_created),
                    $this->titleCase($item->applicant_name),
                    $item->applicant_email,
                    $item->applicant_email_secondary,
                    strtoupper((string) $item->applicant_postcode),
                    $item->applicant_phone,
                    $item->applicant_phone_secondary,
                    $item->applicant_landline,
                    strtoupper((string) $item->job_category),
                    strtoupper((string) $item->job_type),
                    strtoupper((string) $item->job_title),
                    strtoupper((string) $item->job_source),
                    strtoupper((string) $item->moved_tab_to),
                    $item->applicant_experience,
                    $item->details,
                ];

            case 'allNoJob':
                return [
                    $this->formatDate($item->note_created_at),
                    $item->user_name ?? '-',
                    $this->titleCase($item->applicant_name),
                    $item->applicant_email ?: '-',
                    $item->applicant_email_secondary ?: '-',
                    strtoupper((string) ($item->applicant_postcode ?? '-')),
                    $item->applicant_phone ?: '-',
                    $item->applicant_phone_secondary ?: '-',
                    $item->applicant_landline ?: '-',
                    strtoupper((string) ($item->job_category_name ?? '-')),
                    strtoupper((string) ($item->job_type ?? '-')),
                    strtoupper((string) ($item->job_title_name ?? '-')),
                    strtoupper((string) ($item->job_source_name ?? '-')),
                    $item->applicant_experience ?: '-',
                    $item->module_notes_details ?: '-',
                ];

            default:
                return [];
        }
    }

    private function formatDate($value): string
    {
        if ($value === null || $value === '') {
            return 'N/A';
        }

        $timestamp = strtotime((string) $value);

        return $timestamp ? date('d M Y, h:i A', $timestamp) : 'N/A';
    }

    private function titleCase($value): string
    {
        return ucwords(strtolower((string) $value));
    }
}
