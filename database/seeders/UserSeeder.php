<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Horsefly\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Creates the primary super-admin user (ID 1).
 *
 * Credentials:   admin@crm.local  /  Admin@1234!
 * (DemoSeeder also creates this user via firstOrCreate, so running both is safe)
 */
class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Silence model observers so no audit/notification side-effects during seeding
        $dispatcher = User::getEventDispatcher();
        User::unsetEventDispatcher();

        User::firstOrCreate(
            ['email' => 'admin@crm.local'],
            [
                'name'               => 'Admin User',
                'email_verified_at'  => now(),
                'is_admin'           => 1,
                'is_active'          => 1,
                'password'           => Hash::make('Admin@1234!'),
                'remember_token'     => Str::random(10),
            ]
        );

        User::setEventDispatcher($dispatcher);
    }
}
