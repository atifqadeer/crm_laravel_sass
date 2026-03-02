<?php

namespace Database\Factories\Horsefly;

use Horsefly\CrmNote;
use Horsefly\User;
use Horsefly\Applicant;
use Horsefly\Sale;
use Illuminate\Database\Eloquent\Factories\Factory;

class CrmNoteFactory extends Factory
{
    protected $model = CrmNote::class;

    public function definition()
    {
        return [
            'crm_notes_uid' => $this->faker->uuid(),
            'user_id' => User::factory(),
            'applicant_id' => Applicant::factory(),
            'sale_id' => Sale::factory(),
            'details' => $this->faker->paragraphs(3, true),
            'moved_tab_to' => $this->faker->randomElement(['crm_sent', 'crm_decline', 'crm_request', 'crm_request_save', 'crm_reject', 'crm_rebook', 'crm_confirm', 'crm_request_reject', 'crm_start_date', 'crm_paid']),
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
