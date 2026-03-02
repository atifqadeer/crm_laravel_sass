<?php

namespace Database\Factories\Horsefly;

use Horsefly\CVNote;
use Horsefly\User;
use Horsefly\Sale;
use Horsefly\Applicant;
use Illuminate\Database\Eloquent\Factories\Factory;

class CVNoteFactory extends Factory
{
    protected $model = CVNote::class;

    public function definition()
    {
        return [
            'cv_uid' => $this->faker->uuid(),
            'user_id' => User::factory(),
            'sale_id' => Sale::factory(),
            'applicant_id' => Applicant::factory(),
            'details' => $this->faker->paragraphs(2, true),
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
