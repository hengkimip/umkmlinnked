<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Clear cache
        app()['cache']->forget('spatie.permission.cache');

        // Create roles
        Role::firstOrCreate(['name' => 'superadmin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);

        // Create permissions
        Permission::firstOrCreate(['name' => 'view-dashboard', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'view-peta', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'import-data', 'guard_name' => 'web']);

        // Assign to SuperAdmin
        $superadmin = Role::findByName('superadmin');
        $superadmin->syncPermissions(['view-dashboard', 'view-peta', 'import-data']);

        // Assign to Admin
        $admin = Role::findByName('admin');
        $admin->syncPermissions(['view-dashboard', 'import-data']);

        echo "✅ Roles and permissions seeded!\n";
    }
}