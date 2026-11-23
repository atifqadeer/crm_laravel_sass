<?php

namespace App\Http\Controllers;

use App\Services\AiCallOutcomeService;
use App\Services\ApplicantApiUpdateService;
use App\Support\AiCallStatus;
use App\Services\SaleApplicantMatchService;
use App\Support\SettingApiKey;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Two external APIs (token auth: settings key "api_key_ai_automation", separate from the open-sales feed key):
 *
 *   1. Job matches (send data out)
 *      GET  /api/applicants/job-matches?page=1&per_page=100
 *      Sales, radius and options are chosen and published on the CRM page;
 *      the caller never sends sale ids.
 *
 *   2. Applicant update (receive changes back, matched by applicant_id)
 *      POST /api/applicants/update   {"applicant_id":1,"phone":"07..."}
 *                                    or {"applicants":[{...},{...}]}
 *
 *   3. Call outcome (the AI agent reports each call; stored in ai_agent_calls)
 *      POST /api/applicants/call-outcome  {"call_id":"..","applicant_id":1,"job_id":2,"call_status":"answered","outcome":"interested"}
 *                                         or {"calls":[{...},{...}]}
 *
 * In-app page (session auth + permission):
 *   GET  /administrator/applicant-match-api          page
 *   POST /administrator/applicant-match-api/preview  preview a selection
 *   POST /administrator/applicant-match-api/publish  make a selection what API 1 serves
 *   DELETE /administrator/applicant-match-api/publish  stop serving
 */
class ApplicantMatchApiController extends Controller
{
    public function __construct(
        private SaleApplicantMatchService $matches,
        private ApplicantApiUpdateService $updates,
        private AiCallOutcomeService $callOutcomes,
    ) {
    }

    /**
     * API 3 — POST /api/applicants/call-outcome
     * The AI agent reports each call: one record, or {"calls": [...]}.
     */
    public function callOutcome(Request $request): JsonResponse
    {
        $this->assertApiToken($request);

        if (!AiCallStatus::enabled()) {
            return response()->json([
                'success' => false,
                'message' => 'Call outcomes are not enabled yet (the ai_agent_calls table is missing).',
            ], 503);
        }

        $input = $request->all();
        unset($input['api_token']);

        [$records, $error] = $this->callOutcomes->extractRecords($input);
        if ($error !== null) {
            return response()->json(['success' => false, 'message' => $error], 422);
        }

        $payload = $this->callOutcomes->store($records);

        // One record: its own status code. A batch: always 200, one result per call.
        $status = 200;
        if (!array_key_exists('calls', $input)) {
            $status = match ($payload['results'][0]['status']) {
                'created' => 201,
                'invalid' => 422,
                default => 200,
            };
        }

        return response()->json($payload, $status);
    }

    /** CRM pages: AI call history for one applicant (optionally one sale), for the modal. */
    public function callHistory(Request $request, int $applicant): JsonResponse
    {
        if (!AiCallStatus::enabled()) {
            return response()->json(['calls' => []]);
        }

        $saleId = $request->integer('sale_id') ?: null;

        return response()->json([
            'applicant_id' => $applicant,
            'sale_id' => $saleId,
            'calls' => $this->callOutcomes->history($applicant, $saleId),
        ]);
    }

    public function index()
    {
        return view('resources.applicant-match-api', [
            'sales' => $this->matches->activeSaleOptions(),
            'fields' => SaleApplicantMatchService::FIELDS,
            'maxSales' => SaleApplicantMatchService::MAX_SALES,
            'maxPerPage' => SaleApplicantMatchService::MAX_PER_PAGE,
            'defaultLimit' => SaleApplicantMatchService::DEFAULT_LIMIT,
            'maxLimit' => SaleApplicantMatchService::MAX_LIMIT,
            'updatableFields' => ApplicantApiUpdateService::UPDATABLE,
            'maxUpdateRecords' => ApplicantApiUpdateService::MAX_RECORDS,
            'published' => $this->matches->publishedSelection(),
        ]);
    }

    /** Page preview: session-authenticated, returns the exact job-matches payload. */
    public function preview(Request $request): JsonResponse
    {
        $response = $this->respondWithMatches($request->all());

        // The preview table is a server-side DataTable: add its paging keys.
        if ($response->getStatusCode() === 200) {
            $payload = $response->getData(true);
            $payload['draw'] = (int) $request->input('draw', 0);
            $payload['recordsTotal'] = $payload['meta']['total'];
            $payload['recordsFiltered'] = $payload['meta']['total'];
            $response->setData($payload);
        }

        return $response;
    }

    /** Page: save the current form selection as what API 1 serves. */
    public function publish(Request $request): JsonResponse
    {
        try {
            $params = $this->matches->normaliseParams($request->all());
        } catch (ValidationException $e) {
            return $this->validationError($e);
        }

        $selection = $this->matches->publish($params, $request->user() ?? auth()->user());

        return response()->json([
            'success' => true,
            'message' => 'Published. API 1 now sends applicants for these ' . count($selection['sale_ids']) . ' sale(s).',
            'selection' => $selection,
        ]);
    }

    /** Page: stop serving data through API 1. */
    public function unpublish(): JsonResponse
    {
        $this->matches->unpublish();

        return response()->json(['success' => true, 'message' => 'Unpublished. API 1 now returns no data.']);
    }

    /**
     * API 1 — GET /api/applicants/job-matches
     *
     * Serves the selection published from the CRM page. The caller sends no
     * sale ids or radius, only optional page / per_page.
     */
    public function jobMatches(Request $request): JsonResponse
    {
        $this->assertApiToken($request);

        $selection = $this->matches->publishedSelection();
        if (!$selection) {
            return response()->json([
                'success' => true,
                'message' => 'No data has been published from the CRM yet.',
                'fields' => SaleApplicantMatchService::FIELDS,
                'data' => [],
                'meta' => ['total' => 0, 'count' => 0, 'page' => 1, 'per_page' => 0, 'last_page' => 1,
                    'has_more' => false, 'published_at' => null, 'generated_at' => now()->toIso8601String()],
            ]);
        }

        try {
            $params = $this->matches->paramsFromPublished($selection, $request->query());
        } catch (ValidationException $e) {
            return $this->validationError($e);
        }

        return response()->json(
            $this->matches->externalPayload($this->matches->search($params), $selection)
        );
    }

    /** API 2 — POST /api/applicants/update */
    public function updateApplicants(Request $request): JsonResponse
    {
        $this->assertApiToken($request);

        $input = $request->all();
        unset($input['api_token']);

        [$records, $error] = $this->updates->extractRecords($input);
        if ($error !== null) {
            return response()->json(['success' => false, 'message' => $error], 422);
        }

        $payload = $this->updates->update($records);

        // One record: answer with its own status code. A batch is always 200
        // with a result per record, so partial success is never lost.
        $status = 200;
        if (!array_key_exists('applicants', $input)) {
            $status = match ($payload['results'][0]['status']) {
                'not_found' => 404,
                'invalid' => 422,
                default => 200,
            };
        }

        return response()->json($payload, $status);
    }

    private function respondWithMatches(array $input): JsonResponse
    {
        unset($input['api_token']);

        try {
            $params = $this->matches->normaliseParams($input);
        } catch (ValidationException $e) {
            return $this->validationError($e);
        }

        return response()->json($this->matches->search($params));
    }

    /** Always answer in JSON, even when the caller didn't send Accept: application/json. */
    private function validationError(ValidationException $e): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $e->getMessage(),
            'errors' => $e->errors(),
        ], 422);
    }

    private function assertApiToken(Request $request): void
    {
        SettingApiKey::assert($request, SettingApiKey::AI_AUTOMATION);
    }
}
