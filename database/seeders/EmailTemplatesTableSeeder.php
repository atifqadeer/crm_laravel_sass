<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EmailTemplatesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $templates = [
            [
                'title' => 'Add Applicant',
                'slug' => 'add_applicant',
                'template' => '(applicant_name), (source_name)',
                'from_email' => 'noreply@example.com',
                'subject' => 'Add Applicant',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'Request Configuration Email',
                'slug' => 'request_configuration_email',
                'template' => 'Use these variables (applicant_name), (job_title), (unit_name), (insert_datetime), (salary), (distance), (location), (user_name), (job_title), (salary), (distance), (location), (user_name)',
                'from_email' => 'noreply@example.com',
                'subject' => 'Request Configuration Email',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'Request Rejected',
                'slug' => 'request_rejected',
                'template' => 'Use these variables (applicant_name), (website name)',
                'from_email' => 'noreply@example.com',
                'subject' => 'Request Rejected',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'Generic Email',
                'slug' => 'generic_email',
                'template' => 'Use these variables (Applicant Name), (website name)',
                'from_email' => 'noreply@example.com',
                'subject' => 'Generic Email',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'Random Email',
                'slug' => 'random_email',
                'template' => 'Good Morning, We hope this email finds you well.',
                'from_email' => 'noreply@example.com',
                'subject' => 'Random Email',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'Send Job Vacancy Details',
                'slug' => 'send_job_vacancy_details',
                'template' => '<p>Use these variables (job_category), (unit_name), (salary), (qualification), (job_type), (timing), (experience), (location)',
                'from_email' => 'noreply@example.com',
                'subject' => 'Send Job Vacancy Details',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'Scrapped Offices Email',
                'slug' => 'scrapped_offices_email',
                'template' => 'Use these variables (recipient_name), (office_name), (unit_name), (postcode)',
                'from_email' => 'noreply@example.com',
                'subject' => 'Scrapped Offices Email',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'Scrap Bulk Emails',
                'slug' => 'scrap_bulk_emails',
                'template' => 'text',
                'from_email' => 'noreply@example.com',
                'subject' => 'Scrap Bulk Emails',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('email_templates')->insert($templates);
    }
}