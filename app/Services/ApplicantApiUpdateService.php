<?php

namespace App\Services;

use Horsefly\Applicant;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * Updates applicant records sent back by an external system, matched by id.
 *
 * Uses the same field names as the job-matches API so a consumer can send
 * back what it received. Each record is validated and saved on its own:
 * one bad record never blocks the others.
 *
 * Saving through the Eloquent model keeps the ApplicantObserver audit trail.
 */
class ApplicantApiUpdateService
{
    public const MAX_RECORDS = 500;

    /** API field => applicants column */
    public const UPDATABLE = [
        'applicant_name' => 'applicant_name',
        'phone' => 'applicant_phone',
        'email' => 'applicant_email',
    ];

    /**
     * Accepts one record ({"applicant_id":1,...}) or many ({"applicants":[...]}).
     *
     * @return array{0: list<array>, 1: ?string} records and a request-level error
     */
    public function extractRecords(array $input): array
    {
        if (array_key_exists('applicants', $input)) {
            $records = $input['applicants'];
            if (!is_array($records) || !array_is_list($records) || $records === []) {
                return [[], '"applicants" must be a non-empty array of records.'];
            }
        } elseif (array_key_exists('applicant_id', $input)) {
            $records = [$input];
        } else {
            return [[], 'Send one record with "applicant_id", or an "applicants" array of records.'];
        }

        if (count($records) > self::MAX_RECORDS) {
            return [[], 'You can update at most ' . self::MAX_RECORDS . ' applicants per request.'];
        }

        return [$records, null];
    }

    /**
     * @return array{success: bool, summary: array, results: list<array>}
     */
    public function update(array $records): array
    {
        $results = [];
        $summary = ['received' => count($records), 'updated' => 0, 'unchanged' => 0, 'not_found' => 0, 'invalid' => 0];

        foreach ($records as $index => $record) {
            $result = $this->updateOne(is_array($record) ? $record : [], $index);
            $summary[$result['status']]++;
            $results[] = $result;
        }

        return [
            'success' => $summary['invalid'] === 0 && $summary['not_found'] === 0,
            'summary' => $summary,
            'results' => $results,
        ];
    }

    private function updateOne(array $record, int $index): array
    {
        $id = $record['applicant_id'] ?? null;
        $base = ['index' => $index, 'applicant_id' => is_numeric($id) ? (int) $id : $id];

        if (!is_numeric($id) || (int) $id < 1) {
            return $base + ['status' => 'invalid', 'errors' => ['applicant_id' => ['applicant_id must be a positive integer.']]];
        }

        $fields = array_intersect_key($record, self::UPDATABLE);
        if ($fields === []) {
            return $base + ['status' => 'invalid', 'errors' => [
                'fields' => ['Send at least one of: ' . implode(', ', array_keys(self::UPDATABLE)) . '.'],
            ]];
        }

        $applicant = Applicant::query()->whereNull('deleted_at')->find((int) $id);
        if (!$applicant) {
            return $base + ['status' => 'not_found', 'errors' => ['applicant_id' => ['No applicant with this id.']]];
        }

        // Normalise before validating: trim text, keep only digits in phone numbers.
        if (array_key_exists('applicant_name', $fields)) {
            $fields['applicant_name'] = is_string($fields['applicant_name']) ? trim($fields['applicant_name']) : $fields['applicant_name'];
        }
        if (array_key_exists('email', $fields)) {
            $fields['email'] = is_string($fields['email']) ? strtolower(trim($fields['email'])) : $fields['email'];
        }
        if (array_key_exists('phone', $fields)) {
            $fields['phone'] = is_scalar($fields['phone']) ? preg_replace('/\D+/', '', (string) $fields['phone']) : $fields['phone'];
        }

        // Same rules as ApplicantController::update for these columns.
        $validator = Validator::make($fields, [
            'applicant_name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => [
                'sometimes', 'required', 'email', 'max:255',
                Rule::unique('applicants', 'applicant_email')->ignore($applicant->id),
            ],
            'phone' => [
                'sometimes', 'required', 'string', 'max:11',
                Rule::unique('applicants', 'applicant_phone')->ignore($applicant->id),
                Rule::unique('applicants', 'applicant_phone_secondary')->ignore($applicant->id),
                Rule::unique('applicants', 'applicant_landline')->ignore($applicant->id),
            ],
        ], [
            'email.unique' => 'This email is already used by another applicant.',
            'phone.unique' => 'This phone number is already used by another applicant.',
        ]);

        if ($validator->fails()) {
            return $base + ['status' => 'invalid', 'errors' => $validator->errors()->toArray()];
        }

        foreach ($validator->validated() as $field => $value) {
            $applicant->{self::UPDATABLE[$field]} = $value;
        }

        if (!$applicant->isDirty()) {
            return $base + ['status' => 'unchanged', 'changed' => []];
        }

        $changed = array_keys(array_intersect(self::UPDATABLE, array_keys($applicant->getDirty())));
        $applicant->save();

        return $base + ['status' => 'updated', 'changed' => $changed];
    }
}
