<?php

namespace App\Exports;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

/**
 * Export for the Category Wise Resources page. Takes the page's already-filtered
 * query (ResourceController::categoryWiseApplicantsQuery) so the file matches the table.
 *
 * type 'all'    => full applicant rows
 * type 'emails' => only applicants that have an email, with their emails
 *
 * Blocked applicants are left out by the controller (is_blocked = 0).
 */
class CategoryWiseApplicantsExport implements FromCollection, WithHeadings
{
    protected Builder $query;
    protected string $type;

    public function __construct(Builder $query, string $type = 'all')
    {
        $this->query = $query;
        $this->type = $type === 'emails' ? 'emails' : 'all';
    }

    public function collection()
    {
        $rows = $this->query
            ->addSelect(['applicants.applicant_landline', 'applicants.job_type'])
            ->get();

        if ($this->type === 'emails') {
            return $rows
                ->filter(function ($item) {
                    return trim((string) $item->applicant_email) !== ''
                        || trim((string) $item->applicant_email_secondary) !== '';
                })
                ->map(function ($item) {
                    return [
                        'applicant_name' => ucwords(strtolower($item->applicant_name ?? '')),
                        'applicant_email' => trim((string) $item->applicant_email),
                        'applicant_email_secondary' => trim((string) $item->applicant_email_secondary),
                        'job_category' => strtoupper($item->job_category_name ?? ''),
                        'job_title' => strtoupper($item->job_title_name ?? ''),
                        'nursing_home_exp' => match ((string) $item->getRawOriginal('have_nursing_home_experience')) {
                            '1' => 'Yes',
                            '0' => 'No',
                            default => '',
                        },
                    ];
                })
                ->values();
        }

        return $rows->map(function ($item) {
            $contact = fn($value) => $value ?? '';

            return [
                'date' => $item->latest_note_created
                    ? Carbon::parse($item->latest_note_created)->format('d M Y, h:i A')
                    : '',
                'sent_by' => $item->user_name ?? '',
                'applicant_name' => ucwords(strtolower($item->applicant_name ?? '')),
                'applicant_email' => $contact($item->applicant_email),
                'applicant_email_secondary' => $contact($item->applicant_email_secondary),
                'applicant_phone' => $contact($item->applicant_phone),
                'applicant_phone_secondary' => $contact($item->applicant_phone_secondary),
                'applicant_landline' => $contact($item->applicant_landline),
                'applicant_postcode' => strtoupper($item->applicant_postcode ?? ''),
                'job_category' => strtoupper($item->job_category_name ?? ''),
                'job_type' => strtoupper($item->job_type ?? ''),
                'job_title' => strtoupper($item->job_title_name ?? ''),
                'job_source' => $item->job_source_name ?? '',
                'experience' => $item->applicant_experience ?? '',
                'nursing_home_exp' => match ((string) $item->getRawOriginal('have_nursing_home_experience')) {
                    '1' => 'Yes',
                    '0' => 'No',
                    default => '',
                },
            ];
        });
    }

    public function headings(): array
    {
        if ($this->type === 'emails') {
            return ['Applicant Name', 'Email (Primary)', 'Email (Secondary)', 'Job Category', 'Job Title', 'Nursing Home Exp'];
        }

        return [
            'Date',
            'Sent By',
            'Applicant Name',
            'Email (Primary)',
            'Email (Secondary)',
            'Phone (Primary)',
            'Phone (Secondary)',
            'Landline',
            'Postcode',
            'Job Category',
            'Job Type',
            'Job Title',
            'Source',
            'Experience',
            'Nursing Home Exp',
        ];
    }
}
