<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SmsTemplatesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $templates = [
            [
                'title' => 'applicant_welcome_sms',
                'slug' => 'applicant_welcome_sms',
                'template' => 'Dear (applicant_name), Welcome to our platform! We are excited to have you on board.',
                'status' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'crm_send_request',
                'slug' => 'crm_send_request',
                'template' => 'Hi (applicant_name) Congratulations! (unit_name) would like to invite you that your application has been submitted for the position we discussed.',
                'status' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'quality_cleared',
                'slug' => 'quality_cleared',
                'template' => 'Hi Thank you for your time over the phone. I am sharing your resume details with the manager of (unit_name) for the discussed vacancy.',
                'status' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('sms_templates')->insert($templates);
    }
}