<?php

namespace Database\Factories;

use Horsefly\Contact;
use Horsefly\Unit;
use Horsefly\Office;
use Illuminate\Database\Eloquent\Factories\Factory;

class ContactFactory extends Factory
{
    protected $model = Contact::class;

    public function definition()
    {
        // Randomly assign contact to either a Unit or an Office
        $contactable = $this->faker->randomElement([Unit::class, Office::class]);
        $contactableModel = $contactable::factory()->create();

        return [
            'contact_name' => $this->faker->name(),
            'contact_email' => $this->faker->optional()->safeEmail(),
            'contact_phone' => $this->faker->optional()->phoneNumber(),
            'contact_landline' => $this->faker->optional()->phoneNumber(),
            'contact_note' => $this->faker->optional()->sentence(),
            'contactable_id' => $contactableModel->id,
            'contactable_type' => $contactable,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}