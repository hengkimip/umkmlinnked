<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Super Admin
        $superadmin = User::updateOrCreate(
            ['email' => 'superadmin@umkmlinked.id'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('superadmin123'),
                'jabatan' => 'Super Administrator',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );
        $superadmin->assignRole('superadmin');

        // Regular Admin
        $admin = User::updateOrCreate(
            ['email' => 'admin@umkmlinked.id'],
            [
                'name' => 'Admin',
                'password' => Hash::make('admin123'),
                'jabatan' => 'Administrator',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );
        $admin->assignRole('admin');

        echo "✅ Users seeded!\n";
        echo "Super Admin: superadmin@umkmlinked.id / superadmin123\n";
        echo "Admin: admin@umkmlinked.id / admin123\n";
    }
}