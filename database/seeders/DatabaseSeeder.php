<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            // Core: users, permissions, roles — must run in this order
            UserSeeder::class,
            PermissionsTableSeeder::class,
            RoleSeeder::class,

            // IP whitelists for Docker / LAN access
            DeploymentIpSeeder::class,

            // Reference / lookup data
            JobCategoriesSeeder::class,
            JobSourcesSeeder::class,

            // Demo: realistic sample users + applicants (for first-time use)
            DemoSeeder::class,
        ]);
    }
}
