<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class CreateRolesAndUsers extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        echo "\n🔄 Starting seeding process...\n";

        // Clear cache
        app()['cache']->forget('spatie.permission.cache');

        // ==================== CREATE ROLES ====================
        echo "📌 Creating roles...\n";
        
        $superadminRole = Role::firstOrCreate(
            ['name' => 'superadmin'],
            ['guard_name' => 'web']
        );
        echo "  ✅ Created 'superadmin' role\n";

        $adminRole = Role::firstOrCreate(
            ['name' => 'admin'],
            ['guard_name' => 'web']
        );
        echo "  ✅ Created 'admin' role\n";

        // ==================== CREATE PERMISSIONS ====================
        echo "📌 Creating permissions...\n";
        
        $permissions = [
            'view-dashboard',
            'view-peta-interaktif',
            'import-data',
            'manage-users',
            'manage-umkm',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                ['name' => $permission],
                ['guard_name' => 'web']
            );
        }
        echo "  ✅ Created " . count($permissions) . " permissions\n";

        // ==================== ASSIGN PERMISSIONS ====================
        echo "📌 Assigning permissions to roles...\n";
        
        $superadminRole->syncPermissions($permissions);
        echo "  ✅ Assigned all permissions to superadmin\n";

        $adminRole->syncPermissions([
            'view-dashboard',
            'import-data',
            'manage-umkm',
        ]);
        echo "  ✅ Assigned permissions to admin\n";

        // ==================== CREATE USERS ====================
        echo "📌 Creating users...\n";
        
        // Super Admin User
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
        $superadmin->syncRoles(['superadmin']);
        echo "  ✅ Created Super Admin: superadmin@umkmlinked.id\n";

        // Regular Admin User
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
        $admin->syncRoles(['admin']);
        echo "  ✅ Created Admin: admin@umkmlinked.id\n";

        // ==================== VERIFY ====================
        echo "\n✅ Seeding completed successfully!\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        echo "📊 Summary:\n";
        echo "  • Roles created: " . Role::count() . "\n";
        echo "  • Permissions created: " . Permission::count() . "\n";
        echo "  • Users created: " . User::count() . "\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        echo "🔑 Login Credentials:\n";
        echo "  Super Admin:\n";
        echo "    Email: superadmin@umkmlinked.id\n";
        echo "    Password: superadmin123\n";
        echo "\n  Admin:\n";
        echo "    Email: admin@umkmlinked.id\n";
        echo "    Password: admin123\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
    }
}