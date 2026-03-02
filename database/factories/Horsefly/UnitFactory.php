<?php

namespace Database\Factories\Horsefly;

use Horsefly\Unit;
use Horsefly\User;
use Horsefly\Office;
use Illuminate\Database\Eloquent\Factories\Factory;

class UnitFactory extends Factory
{
    protected $model = Unit::class;

    public function definition()
    {
        return [
            'unit_uid' => $this->faker->uuid(),
            'user_id' => User::factory(),
            'office_id' => Office::factory(),
            'unit_name' => $this->faker->company(),
            'unit_postcode' => strtoupper($this->faker->bothify('??## #??')),
            'unit_website' => $this->faker->optional()->url(),
            'unit_notes' => $this->faker->paragraphs(2, true),
            'lat' => $this->faker->optional()->latitude(),
            'lng' => $this->faker->optional()->longitude(),
            'status' => $this->faker->randomElement([0, 1]), // inactive or active
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
