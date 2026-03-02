<?php

namespace Database\Factories\Horsefly;

use Horsefly\History;
use Horsefly\User;
use Horsefly\Applicant;
use Horsefly\Sale;
use Illuminate\Database\Eloquent\Factories\Factory;

class HistoryFactory extends Factory
{
    protected $model = History::class;

    public function definition()
    {
        return [
            'history_uid' => $this->faker->uuid(),
            'user_id' => User::factory(),
            'applicant_id' => Applicant::factory(),
            'sale_id' => Sale::factory(),
            'stage' => $this->faker->randomElement(['interview', 'cleared', 'callback', 'quality', 'crm']),
            'sub_stage' => $this->faker->randomElement([
                'crm_request_no_response', 'crm_no_job_request', 'crm_request_no_job_save', 
                'crm_request_reject', 'crm_request_no_job_reject', 'crm_request_confirm',
                'crm_interview_save', 'crm_request_no_job_confirm', 'crm_rebook', 
                'crm_rebook_save', 'crm_interview_attended', 'crm_prestart_save', 
                'crm_declined', 'crm_interview_not_attended', 'crm_start_date', 
                'crm_start_date_save', 'crm_start_date_back', 'crm_start_date_hold', 
                'crm_start_date_hold_save', 'crm_invoice', 'crm_final_save', 
                'crm_invoice_sent', 'crm_dispute', 'crm_paid', 'quality_cleared', 
                'crm_save', 'quality_cvs_hold', 'quality_cleared_no_job', 'crm_reject', 
                'crm_no_job_reject', 'crm_request', 'crm_request_save'
            ]),
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
