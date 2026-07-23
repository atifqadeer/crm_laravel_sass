<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Horsefly\User;
use Carbon\Carbon;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Insert roles
        DB::table('roles')->insert([
            ['name' => 'super_admin', 'type' => 'super_admin', 'guard_name' => 'web', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['name' => 'admin', 'type' => 'admin', 'guard_name' => 'web', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['name' => 'crm', 'type' => 'crm', 'guard_name' => 'web', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['name' => 'sales', 'type' => 'sales', 'guard_name' => 'web', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['name' => 'quality', 'type' => 'quality', 'guard_name' => 'web', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['name' => 'agent', 'type' => 'agent', 'guard_name' => 'web', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
        ]);

        // Fetch the super_admin role
        $superAdminRole = Role::where('name', 'super_admin')->first();

        // Fetch all permissions
        $allPermissions = Permission::all();

        // Assign all permissions to super_admin role
        $superAdminRole->syncPermissions($allPermissions);

        // OPTIONAL: Assign role to a user (this adds to model_has_roles)
        $user = User::find(1); // Make sure user with id 1 exists
        if ($user) {
            $user->assignRole('super_admin'); // This updates model_has_roles
        }
    }
}
