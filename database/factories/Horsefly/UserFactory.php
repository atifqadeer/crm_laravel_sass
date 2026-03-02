<?php

namespace Database\Factories\Horsefly;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Horsefly\User;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Horsefly\User>
 */
class UserFactory extends Factory
{
    protected $model = User::class;
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->userName() . '.' . $this->faker->unique()->safeEmail(),
            'email_verified_at' => now(),
            'is_active' => $this->faker->boolean(90), // 90% active
            'is_admin' => $this->faker->boolean(10),  // 10% admin
            'password' => bcrypt('password'), // explicitly use bcrypt
            'remember_token' => Str::random(10),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
