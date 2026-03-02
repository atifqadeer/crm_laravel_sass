<?php

namespace Database\Factories\Horsefly;

use Horsefly\SaleNote;
use Horsefly\Sale;
use Horsefly\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class SaleNoteFactory extends Factory
{
    protected $model = SaleNote::class;

    public function definition()
    {
        return [
            'sales_notes_uid' => $this->faker->uuid(),
            'sale_id' => Sale::factory(),
            'user_id' => User::factory(),
            'sale_note' => $this->faker->paragraphs(2, true),
            'status' => $this->faker->randomElement([0,1]), // 0=inactive, 1=active
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
