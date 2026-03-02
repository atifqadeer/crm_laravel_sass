<?php

namespace Database\Factories;

use Horsefly\Office;
use Horsefly\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class OfficeFactory extends Factory
{
    protected $model = Office::class;

    public function definition()
    {
        return [
            'office_uid' => $this->faker->uuid(),
            'user_id' => User::factory(), // link to a user
            'office_name' => $this->faker->company(),
            'office_postcode' => $this->faker->postcode(),
            'office_website' => $this->faker->optional()->url(),
            'office_notes' => $this->faker->optional()->paragraphs(2, true),
            'office_lat' => $this->faker->optional()->latitude(),
            'office_lng' => $this->faker->optional()->longitude(),
            'status' => $this->faker->randomElement([0,1]), // inactive or active
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}