<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Alias lama — kini memakai seeder peran & akun standar (PRD v1.1).
 */
class CreateRolesAndUsers extends Seeder
{
    public function run(): void
    {
        $this->call([RolePermissionSeeder::class, UserSeeder::class]);
    }
}
