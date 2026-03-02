<?php

namespace Database\Factories\Horsefly;

use Horsefly\Sale;
use Horsefly\User;
use Horsefly\Office;
use Horsefly\Unit;
use Horsefly\JobCategory;
use Horsefly\JobTitle;
use Illuminate\Database\Eloquent\Factories\Factory;

class SaleFactory extends Factory
{
    protected $model = Sale::class;

    public function definition()
    {
        return [
            'sale_uid' => $this->faker->uuid(),
            'user_id' => User::factory(),
            'office_id' => Office::factory(),
            'unit_id' => Unit::factory(),
            'job_category_id' => JobCategory::factory(),
            'job_title_id' => JobTitle::factory(),
            'sale_postcode' => strtoupper($this->faker->bothify('??## #??')),
            'position_type' => $this->faker->randomElement(['full-time', 'part-time', 'contract']),
            'job_type' => $this->faker->randomElement(['permanent','temporary','contract']),
            'timing' => $this->faker->optional()->sentence(),
            'salary' => $this->faker->optional()->numerify('£###00'),
            'experience' => $this->faker->optional()->paragraph(),
            'qualification' => $this->faker->optional()->sentence(),
            'benefits' => $this->faker->optional()->paragraph(),
            'lat' => $this->faker->optional()->latitude(),
            'lng' => $this->faker->optional()->longitude(),
            'job_description' => $this->faker->optional()->paragraphs(3, true),
            'is_on_hold' => $this->faker->randomElement([0,1,2]),
            'is_re_open' => $this->faker->randomElement([0,1,2]),
            'cv_limit' => $this->faker->numberBetween(5,12),
            'sale_notes' => $this->faker->optional()->paragraph(),
            'status' => $this->faker->randomElement([0,1,2,3]),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
