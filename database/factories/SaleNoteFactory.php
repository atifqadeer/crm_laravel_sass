<?php

namespace Database\Factories;

use Horsefly\SaleNote;
use Horsefly\Sale;
use Horsefly\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class SaleNoteFactory extends Factory
{
    protected $model = SaleNote::class;

    public function definition()
    {
        $user = User::factory()->create();
        $sale = Sale::factory()->for($user, 'user')->create();

        return [
            'sales_notes_uid' => $this->faker->uuid(),
            'sale_id' => $sale->id,
            'user_id' => $user->id,
            'sale_note' => $this->faker->paragraphs(2, true),
            'status' => $this->faker->randomElement([0,1]), // 0=inactive, 1=active
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}