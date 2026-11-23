<?php

namespace App\Services;

use App\Support\AiCallStatus;
use Horsefly\AiAgentCall;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * Stores call results sent by the AI agent (POST /api/applicants/call-outcome).
 *
 * Each call is validated and saved on its own. Re-sending the same call_id
 * updates that call (e.g. the agent adds the transcript later) instead of
 * creating a duplicate. The whole request is kept in `payload`.
 */
class AiCallOutcomeService
{
    public const MAX_RECORDS = 500;

    /** @return array{0: list<array>, 1: ?string} records and a request-level error */
    public function extractRecords(array $input): array
    {
        if (array_key_exists('calls', $input)) {
            $records = $input['calls'];
            if (!is_array($records) || !array_is_list($records) || $records === []) {
                return [[], '"calls" must be a non-empty array of call records.'];
            }
        } elseif (array_key_exists('call_id', $input) || array_key_exists('applicant_id', $input)) {
            $records = [$input];
        } else {
            return [[], 'Send one call record, or a "calls" array of call records.'];
        }

        if (count($records) > self::MAX_RECORDS) {
            return [[], 'You can send at most ' . self::MAX_RECORDS . ' calls per request.'];
        }

        return [$records, null];
    }

    public function store(array $records): array
    {
        $results = [];
        $summary = ['received' => count($records), 'created' => 0, 'updated' => 0, 'invalid' => 0];

        foreach ($records as $index => $record) {
            $result = $this->storeOne(is_array($record) ? $record : [], $index);
            $summary[$result['status']]++;
            $results[] = $result;
        }

        return [
            'success' => $summary['invalid'] === 0,
            'summary' => $summary,
            'results' => $results,
        ];
    }

    private function storeOne(array $record, int $index): array
    {
        $base = ['index' => $index, 'call_id' => $record['call_id'] ?? null];

        $validator = Validator::make($record, [
            'call_id' => ['required', 'string', 'max:100'],
            'applicant_id' => [
                'required', 'integer',
                Rule::exists('applicants', 'id')->whereNull('deleted_at'),
            ],
            'job_id' => ['required', 'integer', Rule::exists('sales', 'id')],
            'call_status' => ['required', Rule::in(AiCallStatus::CALL_STATUSES)],
            'outcome' => [
                'nullable', 'required_if:call_status,answered', 'prohibited_unless:call_status,answered',
                Rule::in(AiCallStatus::OUTCOMES),
            ],
            'callback_at' => ['nullable', 'required_if:outcome,callback', 'date'],
            'called_at' => ['nullable', 'date'],
            'duration_seconds' => ['nullable', 'integer', 'min:0', 'max:86400'],
            'summary' => ['nullable', 'string', 'max:65000'],
            'transcript' => ['nullable', 'string'],
            'recording_url' => ['nullable', 'url:http,https', 'max:2048'],
        ], [
            'applicant_id.exists' => 'No applicant with this id.',
            'job_id.exists' => 'No job (sale) with this id.',
            'outcome.required_if' => 'outcome is required when call_status is answered.',
            'outcome.prohibited_unless' => 'outcome is only allowed when call_status is answered.',
            'callback_at.required_if' => 'callback_at is required when outcome is callback.',
        ]);

        if ($validator->fails()) {
            return $base + ['status' => 'invalid', 'errors' => $validator->errors()->toArray()];
        }

        $data = $validator->validated();
        $status = AiCallStatus::statusFor($data['call_status'], $data['outcome'] ?? null);

        $attributes = [
            'applicant_id' => (int) $data['applicant_id'],
            'sale_id' => (int) $data['job_id'],
            'call_status' => $data['call_status'],
            'outcome' => $data['outcome'] ?? null,
            'status' => $status,
            'callback_at' => isset($data['callback_at']) ? Carbon::parse($data['callback_at']) : null,
            'called_at' => isset($data['called_at']) ? Carbon::parse($data['called_at']) : now(),
            'duration_seconds' => $data['duration_seconds'] ?? null,
            'summary' => $data['summary'] ?? null,
            'transcript' => $data['transcript'] ?? null,
            'recording_url' => $data['recording_url'] ?? null,
            'payload' => $record,
        ];

        // Lock the call_id row so two retries arriving together can't both insert.
        return DB::transaction(function () use ($data, $attributes, $base, $status) {
            $call = AiAgentCall::where('call_id', $data['call_id'])->lockForUpdate()->first();

            if ($call && ($call->applicant_id !== $attributes['applicant_id'] || $call->sale_id !== $attributes['sale_id'])) {
                return $base + ['status' => 'invalid', 'errors' => [
                    'call_id' => ['This call_id is already stored for a different applicant or job.'],
                ]];
            }

            if ($call) {
                // Keep what was stored when a later update leaves a field out.
                foreach (['summary', 'transcript', 'recording_url', 'duration_seconds', 'callback_at'] as $keep) {
                    if ($attributes[$keep] === null) {
                        unset($attributes[$keep]);
                    }
                }
                $call->update($attributes);

                return $base + ['status' => 'updated', 'id' => $call->id, 'ai_status' => $status];
            }

            $call = AiAgentCall::create($attributes + ['call_id' => $data['call_id']]);

            return $base + ['status' => 'created', 'id' => $call->id, 'ai_status' => $status];
        });
    }

    /** History for the CRM modal: every call for an applicant, optionally for one sale. */
    public function history(int $applicantId, ?int $saleId = null): array
    {
        return AiAgentCall::query()
            ->with(['sale:id,job_title_id,office_id,unit_id,sale_postcode', 'sale.jobTitle:id,name', 'sale.office:id,office_name', 'sale.unit:id,unit_name'])
            ->where('applicant_id', $applicantId)
            ->when($saleId, fn($q) => $q->where('sale_id', $saleId))
            ->orderByDesc('called_at')
            ->orderByDesc('id')
            ->limit(200)
            ->get()
            ->map(fn(AiAgentCall $call) => [
                'id' => $call->id,
                'call_id' => $call->call_id,
                'status' => $call->status,
                'status_label' => AiCallStatus::label($call->status),
                'badge_class' => AiCallStatus::STATUSES[$call->status][1] ?? 'bg-light text-dark border',
                'called_at' => optional($call->called_at)->format('d M Y H:i'),
                'callback_at' => optional($call->callback_at)->format('d M Y H:i'),
                'duration_seconds' => $call->duration_seconds,
                'summary' => $call->summary,
                'transcript' => $call->transcript,
                'recording_url' => $call->recording_url,
                'job' => $call->sale ? [
                    'id' => $call->sale->id,
                    'title' => optional($call->sale->jobTitle)->name,
                    'office' => optional($call->sale->office)->office_name,
                    'unit' => optional($call->sale->unit)->unit_name,
                    'postcode' => strtoupper((string) $call->sale->sale_postcode),
                ] : null,
            ])
            ->all();
    }
}
