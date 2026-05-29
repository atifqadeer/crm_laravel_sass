<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Horsefly\User;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        // ── Create roles (idempotent) ─────────────────────────────────────────
        $roleNames = ['super_admin', 'admin', 'crm', 'sales', 'quality'];

        foreach ($roleNames as $name) {
            Role::firstOrCreate(
                ['name' => $name, 'guard_name' => 'web']
            );
        }

        // ── Grant super_admin every permission ───────────────────────────────
        $superAdminRole  = Role::where('name', 'super_admin')->first();
        $allPermissions  = Permission::all();

        if ($superAdminRole && $allPermissions->isNotEmpty()) {
            $superAdminRole->syncPermissions($allPermissions);
        }

        // ── Assign super_admin role to user ID 1 (the first admin) ───────────
        $user = User::find(1);
        if ($user) {
            $user->syncRoles(['super_admin']);
        }
    }
}
