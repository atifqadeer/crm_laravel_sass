<?php

namespace Database\Factories;

use Horsefly\ApplicantNote;
use Horsefly\User;
use Horsefly\Applicant;
use Illuminate\Database\Eloquent\Factories\Factory;

class ApplicantNoteFactory extends Factory
{
    protected $model = ApplicantNote::class;

    public function definition()
    {
        return [
            'note_uid' => $this->faker->uuid(),
            'user_id' => User::factory(),
            'applicant_id' => Applicant::factory(),
            'details' => $this->faker->paragraphs($nb = 2, $asText = true),
            'moved_tab_to' => $this->faker->optional()->randomElement(['CV', 'Notes', 'Interview', 'CRM', 'Invoice']),
            'status' => $this->faker->randomElement([0,1]), // active/inactive
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}