<?php

namespace App\Services;

use App\Support\AiCallStatus;
use Horsefly\Setting;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * Matches eligible applicants to active sales by distance.
 *
 * One output row per (applicant, sale) pair: an applicant within the radius of
 * three selected sales appears three times, once per job.
 *
 * Applicant eligibility:
 *   - applicants.status = 1, not blocked, not soft-deleted, has coordinates
 *   - not paid (paid_status <> 'close' and is_in_crm_paid = 0)
 *   - no active rejection (crm_rejected_cv.status = 1)
 *   - optionally the same job category as the sale (match_category, default on)
 *   - the AI agent's re-call rules (AiCallStatus::applyRecallRules)
 *
 * Sale eligibility: status = 1, not on hold, not soft-deleted, has coordinates.
 */
class SaleApplicantMatchService
{
    public const FIELDS = [
        'applicant_id', 'applicant_name', 'phone', 'email',
        'job_id', 'job_title', 'employer', 'location', 'salary', 'benefits',
        'qualifications', 'experience', 'job_type', 'working_hours',
        'job_description',
    ];

    public const MAX_SALES = 50;
    public const MAX_RADIUS_KM = 200;
    public const MAX_PER_PAGE = 1000;
    public const DEFAULT_LIMIT = 500;
    public const MAX_LIMIT = 50000;
    private const KM_PER_MILE = 1.609344;
    private const KM_PER_DEGREE_LAT = 111.045;
    public const SELECTION_KEY = 'applicant_match_api_selection';

    /**
     * Validate and normalise request input (query string or JSON body).
     *
     * @return array{sale_ids: list<int>, radius: float, unit: string, radius_km: float, match_category: bool, format: string, limit: int, offset: int, page: int, per_page: int}
     *
     * @throws ValidationException
     */
    public function normaliseParams(array $input): array
    {
        // Accept sale_ids as an array, a CSV string ("1,2,3") or a single id.
        $saleIds = $input['sale_ids'] ?? [];
        if (is_string($saleIds) || is_numeric($saleIds)) {
            $saleIds = explode(',', (string) $saleIds);
        }
        $input['sale_ids'] = array_values(array_unique(array_filter(
            array_map(fn($id) => is_numeric(trim((string) $id)) ? (int) trim((string) $id) : $id, (array) $saleIds),
            fn($id) => $id !== '' && $id !== null
        )));

        $data = Validator::make($input, [
            'sale_ids' => ['required', 'array', 'min:1', 'max:' . self::MAX_SALES],
            'sale_ids.*' => ['integer', 'min:1'],
            'radius' => ['required', 'numeric', 'gt:0'],
            'unit' => ['nullable', 'in:km,miles'],
            'match_category' => ['nullable'],
            'format' => ['nullable', 'in:text,html'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:' . self::MAX_LIMIT],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:' . self::MAX_PER_PAGE],
            // DataTables paging (preview table): start = offset, length = page size
            'start' => ['nullable', 'integer', 'min:0'],
            'length' => ['nullable', 'integer', 'min:1', 'max:' . self::MAX_PER_PAGE],
        ], [
            'limit.max' => 'The send limit may not be greater than ' . number_format(self::MAX_LIMIT) . '.',
            'sale_ids.required' => 'Select at least one sale (sale_ids).',
            'sale_ids.max' => 'You can select at most ' . self::MAX_SALES . ' sales per request.',
        ])->validate();

        $unit = $data['unit'] ?? 'km';
        $radius = (float) $data['radius'];
        $radiusKm = $unit === 'miles' ? $radius * self::KM_PER_MILE : $radius;

        if ($radiusKm > self::MAX_RADIUS_KM) {
            throw ValidationException::withMessages([
                'radius' => 'The radius may not be greater than ' . self::MAX_RADIUS_KM . ' km.',
            ]);
        }

        if (isset($data['length'])) {
            $perPage = (int) $data['length'];
            $offset = (int) ($data['start'] ?? 0);
        } else {
            $perPage = (int) ($data['per_page'] ?? 100);
            $offset = ((int) ($data['page'] ?? 1) - 1) * $perPage;
        }

        return [
            'sale_ids' => $data['sale_ids'],
            'radius' => $radius,
            'unit' => $unit,
            'radius_km' => round($radiusKm, 3),
            'match_category' => filter_var($input['match_category'] ?? true, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? true,
            'format' => $data['format'] ?? 'text',
            'limit' => (int) ($data['limit'] ?? self::DEFAULT_LIMIT),
            'offset' => $offset,
            'page' => intdiv($offset, $perPage) + 1,
            'per_page' => $perPage,
        ];
    }

    /**
     * Save the CRM page's current selection as the one the external API serves.
     * Stored as JSON in the settings table (key SELECTION_KEY).
     */
    public function publish(array $params, $user): array
    {
        $selection = [
            'sale_ids' => array_values(array_map('intval', $params['sale_ids'])),
            'radius' => $params['radius'],
            'unit' => $params['unit'],
            'match_category' => $params['match_category'],
            'format' => $params['format'],
            'limit' => $params['limit'],
            'published_at' => now()->toIso8601String(),
            'published_by_id' => $user?->id,
            'published_by' => $user?->name,
        ];

        Setting::updateOrCreate(
            ['key' => self::SELECTION_KEY],
            ['value' => json_encode($selection), 'type' => 'json', 'group' => 'applicant_match_api']
        );

        return $selection;
    }

    /** Stop serving data: the external API returns an empty list until the next publish. */
    public function unpublish(): void
    {
        Setting::where('key', self::SELECTION_KEY)->delete();
    }

    /** The selection last published from the CRM, or null if none. */
    public function publishedSelection(): ?array
    {
        $value = Setting::where('key', self::SELECTION_KEY)->value('value');
        $selection = $value ? json_decode($value, true) : null;

        return is_array($selection) && !empty($selection['sale_ids']) ? $selection : null;
    }

    /**
     * Build search params from the published selection. The caller only
     * controls paging; sales, radius and the other options come from the CRM.
     *
     * @throws ValidationException
     */
    public function paramsFromPublished(array $selection, array $input): array
    {
        $paging = Validator::make($input, [
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:' . self::MAX_PER_PAGE],
        ])->validate();

        return $this->normaliseParams([
            'sale_ids' => $selection['sale_ids'],
            'radius' => $selection['radius'],
            'unit' => $selection['unit'] ?? 'km',
            'match_category' => $selection['match_category'] ?? true,
            'format' => $selection['format'] ?? 'text',
            'limit' => $selection['limit'] ?? self::DEFAULT_LIMIT,
            'page' => $paging['page'] ?? 1,
            'per_page' => $paging['per_page'] ?? 100,
        ]);
    }

    /**
     * External version of a search payload: only the records and paging.
     * Which sales were used, how many per sale, and the radius stay inside the CRM.
     */
    public function externalPayload(array $payload, array $selection): array
    {
        $meta = $payload['meta'];

        return [
            'success' => true,
            'fields' => $payload['fields'],
            'data' => $payload['data'],
            'meta' => [
                'total' => $meta['total'],
                'count' => $meta['count'],
                'page' => $meta['page'],
                'per_page' => $meta['per_page'],
                'last_page' => $meta['last_page'],
                'has_more' => $meta['has_more'],
                'published_at' => $selection['published_at'] ?? null,
                'generated_at' => $meta['generated_at'],
            ],
        ];
    }

    /**
     * Run the match and return the API payload.
     */
    public function search(array $params): array
    {
        $activeSaleIds = $this->activeSalesQuery()
            ->whereIn('sales.id', $params['sale_ids'])
            ->pluck('sales.id')
            ->map(fn($id) => (int) $id)
            ->all();

        $ignored = array_values(array_diff($params['sale_ids'], $activeSaleIds));

        $total = 0;
        $totalMatches = 0;
        $rows = [];
        $perSale = [];

        if ($activeSaleIds) {
            $query = $this->matchQuery($activeSaleIds, $params['radius_km'], $params['match_category']);

            $perSale = (clone $query)
                ->groupBy('sales.id')
                ->selectRaw('sales.id as sale_id, COUNT(*) as applicants')
                ->pluck('applicants', 'sale_id')
                ->map(fn($count) => (int) $count)
                ->all();
            $totalMatches = array_sum($perSale);
            // The send limit caps everything the API hands out, across all pages.
            $total = min($totalMatches, $params['limit']);
            $take = min($params['per_page'], $total - $params['offset']);

            if ($take > 0) {
                // Interleave sales: each sale's nearest applicant first, then each
                // sale's second nearest, and so on. With a limit, a sale with
                // thousands of matches can't crowd the others out.
                // Ranked on ids only; sorting the wide text columns of every match
                // just to keep one page was ~10x slower.
                $ranked = $query->select([
                    'sales.id as sale_id',
                    'applicants.id as applicant_id',
                    DB::raw('ROW_NUMBER() OVER (PARTITION BY sales.id ORDER BY ' . $this->distanceSql() . ', applicants.id) as sale_rank'),
                ]);

                $pairs = DB::query()
                    ->fromSub($ranked, 'ranked')
                    ->orderBy('sale_rank')
                    ->orderBy('sale_id')
                    ->offset($params['offset'])
                    ->limit($take)
                    ->get(['sale_id', 'applicant_id']);

                $rows = $this->hydrate($pairs, $params['format']);
            }
        }

        $lastPage = max(1, (int) ceil($total / $params['per_page']));

        return [
            'success' => true,
            'fields' => self::FIELDS,
            'data' => $rows,
            'meta' => [
                'total' => $total,
                'count' => count($rows),
                'page' => $params['page'],
                'per_page' => $params['per_page'],
                'last_page' => $lastPage,
                'has_more' => $params['offset'] + count($rows) < $total,
                'limit' => $params['limit'],
                'total_matches' => $totalMatches,
                'radius' => $params['radius'],
                'unit' => $params['unit'],
                'radius_km' => $params['radius_km'],
                'match_category' => $params['match_category'],
                'format' => $params['format'],
                'sale_ids' => $activeSaleIds,
                'ignored_sale_ids' => $ignored,
                'applicants_per_sale' => (object) $perSale,
                'generated_at' => now()->toIso8601String(),
            ],
        ];
    }

    /**
     * Active sales with coordinates: the only sales a match can use.
     */
    public function activeSalesQuery(): Builder
    {
        return DB::table('sales')
            ->where('sales.status', 1)
            ->where('sales.is_on_hold', 0)
            ->whereNull('sales.deleted_at')
            ->whereNotNull('sales.lat')
            ->whereNotNull('sales.lng')
            ->where('sales.lat', '<>', 0)
            ->where('sales.lng', '<>', 0);
    }

    /**
     * Option list for the page's sales picker.
     */
    public function activeSaleOptions()
    {
        return $this->activeSalesQuery()
            ->leftJoin('job_titles', 'job_titles.id', '=', 'sales.job_title_id')
            ->leftJoin('job_categories', 'job_categories.id', '=', 'sales.job_category_id')
            ->leftJoin('offices', 'offices.id', '=', 'sales.office_id')
            ->leftJoin('units', 'units.id', '=', 'sales.unit_id')
            ->orderByDesc('sales.id')
            ->get([
                'sales.id',
                'sales.sale_postcode',
                'sales.job_type',
                'job_titles.name as job_title',
                'job_categories.name as job_category',
                'offices.office_name',
                'units.unit_name',
            ]);
    }

    private function matchQuery(array $saleIds, float $radiusKm, bool $matchCategory): Builder
    {
        // Bounding box first (cheap comparisons), then the exact sphere distance.
        $latDelta = $radiusKm / self::KM_PER_DEGREE_LAT;
        $meters = $radiusKm * 1000;

        $query = $this->activeSalesQuery()
            ->whereIntegerInRaw('sales.id', $saleIds)
            ->join('applicants', function (JoinClause $join) use ($latDelta, $meters, $matchCategory) {
                $join->whereRaw('applicants.lat BETWEEN sales.lat - ? AND sales.lat + ?', [$latDelta, $latDelta])
                    ->whereRaw(
                        'applicants.lng BETWEEN sales.lng - ? / COS(RADIANS(sales.lat)) AND sales.lng + ? / COS(RADIANS(sales.lat))',
                        [$latDelta, $latDelta]
                    )
                    ->whereRaw($this->distanceSql() . ' <= ?', [$meters]);

                if ($matchCategory) {
                    $join->whereColumn('applicants.job_category_id', 'sales.job_category_id');
                }
            })
            ->leftJoin('job_titles', 'job_titles.id', '=', 'sales.job_title_id')
            ->leftJoin('offices', 'offices.id', '=', 'sales.office_id')
            ->leftJoin('units', 'units.id', '=', 'sales.unit_id')
            ->where('applicants.status', 1)
            ->where('applicants.is_blocked', 0)
            ->whereNull('applicants.deleted_at')
            ->where('applicants.is_in_crm_paid', 0)
            ->where(fn($q) => $q->whereNull('applicants.paid_status')->orWhere('applicants.paid_status', '<>', 'close'))
            ->whereNotExists(function ($q) {
                $q->selectRaw('1')
                    ->from('crm_rejected_cv')
                    ->whereColumn('crm_rejected_cv.applicant_id', 'applicants.id')
                    ->where('crm_rejected_cv.status', 1);
            });

        // Don't hand the AI agent people it has already dealt with for that job
        // (or who asked not to be called). See AiCallStatus::applyRecallRules().
        AiCallStatus::applyRecallRules($query, $saleIds);

        return $query;
    }

    private function distanceSql(): string
    {
        return 'ST_Distance_Sphere(POINT(applicants.lng, applicants.lat), POINT(sales.lng, sales.lat))';
    }

    /**
     * Load the output fields for one page of (sale_id, applicant_id) pairs,
     * keeping the page order.
     */
    private function hydrate($pairs, string $format): array
    {
        $applicants = DB::table('applicants')
            ->whereIntegerInRaw('id', $pairs->pluck('applicant_id')->unique()->all())
            ->get(['id', 'applicant_name', 'applicant_phone', 'applicant_email'])
            ->keyBy('id');

        $sales = DB::table('sales')
            ->leftJoin('job_titles', 'job_titles.id', '=', 'sales.job_title_id')
            ->leftJoin('offices', 'offices.id', '=', 'sales.office_id')
            ->leftJoin('units', 'units.id', '=', 'sales.unit_id')
            ->whereIntegerInRaw('sales.id', $pairs->pluck('sale_id')->unique()->all())
            ->get([
                'sales.id',
                'job_titles.name as job_title',
                'offices.office_name as employer',
                'units.unit_name as location',
                'sales.salary',
                'sales.benefits',
                'sales.qualification',
                'sales.experience',
                'sales.job_type',
                'sales.timing',
                'sales.job_description',
            ])
            ->keyBy('id')
            ->map(fn($sale) => [
                'job_id' => (int) $sale->id,
                'job_title' => $sale->job_title,
                'employer' => $sale->employer,
                'location' => $sale->location,
                'salary' => $this->text($sale->salary, $format),
                'benefits' => $this->text($sale->benefits, $format),
                'qualifications' => $this->text($sale->qualification, $format),
                'experience' => $this->text($sale->experience, $format),
                'job_type' => $sale->job_type,
                'working_hours' => $this->text($sale->timing, $format),
                'job_description' => $this->text($sale->job_description, $format),
            ]);

        return $pairs->map(function ($pair) use ($applicants, $sales) {
            $applicant = $applicants[$pair->applicant_id];

            return [
                'applicant_id' => (int) $applicant->id,
                'applicant_name' => $applicant->applicant_name,
                'phone' => $applicant->applicant_phone,
                'email' => $applicant->applicant_email,
            ] + $sales[$pair->sale_id];
        })->all();
    }

    /**
     * Sale text fields are stored as editor HTML, often pasted from Word.
     * 'text' (default) returns clean plain text with line breaks; 'html' returns it as stored.
     */
    private function text(?string $value, string $format): ?string
    {
        if ($value === null || $format === 'html') {
            return $value;
        }

        // Word conditional comments / XML, other comments, and style/script blocks.
        $value = preg_replace('/<!--.*?-->/s', '', $value);
        $value = preg_replace('/<(style|script|xml)\b[^>]*>.*?<\/\1>/is', '', $value);
        // Block-level tags become line breaks, list items become bullets.
        $value = preg_replace('/<li\b[^>]*>/i', "\n• ", $value);
        $value = preg_replace('/<(br|\/p|\/div|\/h[1-6]|\/li|\/tr)\b[^>]*>/i', "\n", $value);
        $value = html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $value = str_replace("\u{00A0}", ' ', $value);
        $value = preg_replace('/[ \t]+/', ' ', $value);
        $value = preg_replace('/ *\n */', "\n", $value);
        $value = preg_replace('/\n{3,}/', "\n\n", $value);

        $value = trim($value);

        return $value === '' ? null : $value;
    }
}
