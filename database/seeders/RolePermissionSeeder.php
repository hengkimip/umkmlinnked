<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // Peran sesuai PRD v1.1 (FR-01)
        $superAdmin = Role::firstOrCreate(['name' => User::ROLE_SUPER_ADMIN, 'guard_name' => 'web']);
        $adminOpd   = Role::firstOrCreate(['name' => User::ROLE_ADMIN_OPD, 'guard_name' => 'web']);

        $permissions = [
            'view-dashboard',
            'view-peta-interaktif',
            'import-data',
            'manage-umkm',
            'manage-users',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $superAdmin->syncPermissions($permissions);
        $adminOpd->syncPermissions(['view-dashboard', 'import-data', 'manage-umkm']);

        $this->command?->info('Roles dan permissions selesai: super-admin, admin-opd.');
    }
}
