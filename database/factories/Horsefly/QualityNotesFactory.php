<?php

namespace Database\Factories\Horsefly;

use Horsefly\QualityNotes;
use Horsefly\User;
use Horsefly\Applicant;
use Horsefly\Sale;
use Illuminate\Database\Eloquent\Factories\Factory;

class QualityNotesFactory extends Factory
{
    protected $model = QualityNotes::class;

    public function definition()
    {
        return [
            'quality_notes_uid' => $this->faker->uuid(),
            'user_id' => User::factory(),
            'applicant_id' => Applicant::factory(),
            'sale_id' => Sale::factory(),
            'details' => $this->faker->paragraphs(2, true),
            'moved_tab_to' => $this->faker->randomElement(['cleared', 'cleared_no_job', 'rejected', 'quality_sent', 'quality_clear', 'quality_reject']),
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
