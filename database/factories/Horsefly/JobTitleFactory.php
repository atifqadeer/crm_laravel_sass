<?php

namespace Database\Factories\Horsefly;

use Horsefly\JobTitle;
use Horsefly\JobCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

class JobTitleFactory extends Factory
{
    protected $model = JobTitle::class;

    public function definition()
    {
        return [
            'name' => $this->faker->jobTitle() . ' ' . $this->faker->numberBetween(1, 1000),
            'type' => $this->faker->randomElement(['full-time', 'part-time', 'contract']),
            'job_category_id' => JobCategory::factory(),
            'description' => $this->faker->sentence(),
            'is_active' => true,
        ];
    }
}
