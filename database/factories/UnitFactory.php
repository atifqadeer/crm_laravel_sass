<?php

namespace Database\Factories;

use Horsefly\Unit;
use Horsefly\User;
use Horsefly\Office;
use Illuminate\Database\Eloquent\Factories\Factory;

class UnitFactory extends Factory
{
    protected $model = Unit::class;

    public function definition()
    {
        $office = Office::factory()->create(); // create a related office
        $user = User::factory()->create();     // create a related user

        return [
            'unit_uid' => $this->faker->uuid(),
            'user_id' => $user->id,
            'office_id' => $office->id,
            'unit_name' => $this->faker->company(),
            'unit_postcode' => $this->faker->postcode(),
            'unit_website' => $this->faker->optional()->url(),
            'unit_notes' => $this->faker->optional()->paragraphs(2, true),
            'lat' => $this->faker->optional()->latitude(),
            'lng' => $this->faker->optional()->longitude(),
            'status' => $this->faker->randomElement([0, 1]), // inactive or active
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}