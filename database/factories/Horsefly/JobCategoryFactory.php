<?php

namespace Database\Factories\Horsefly;

use Horsefly\JobCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

class JobCategoryFactory extends Factory
{
    protected $model = JobCategory::class;

    public function definition()
    {
        return [
            'name' => $this->faker->word() . ' ' . $this->faker->numberBetween(1, 1000),
            'description' => $this->faker->sentence(),
            'is_active' => true,
        ];
    }
}
