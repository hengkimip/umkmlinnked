<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class SetupRoles extends Seeder
{
    public function run(): void
    {
        // Create roles
        Role::firstOrCreate(['name' => 'superadmin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);

        // Create users
        $superadmin = User::firstOrCreate(
            ['email' => 'superadmin@umkmlinked.id'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('superadmin123'),
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );
        $superadmin->assignRole('superadmin');

        $admin = User::firstOrCreate(
            ['email' => 'admin@umkmlinked.id'],
            [
                'name' => 'Admin',
                'password' => Hash::make('admin123'),
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );
        $admin->assignRole('admin');

        echo "✅ Roles and users created!\n";
    }
}