<?php

namespace Database\Factories\Horsefly;

use Horsefly\JobSource;
use Illuminate\Database\Eloquent\Factories\Factory;

class JobSourceFactory extends Factory
{
    protected $model = JobSource::class;

    public function definition()
    {
        return [
            'name' => $this->faker->company() . ' ' . $this->faker->numberBetween(1, 1000),
            'description' => $this->faker->sentence(),
            'is_active' => true,
        ];
    }
}
