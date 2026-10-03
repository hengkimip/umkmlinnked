<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

/**
 * PRD v1.1 FR-01: peran memakai Spatie dengan nama `super-admin` dan `admin-opd`.
 * Seeder lama membuat `superadmin` dan `admin`; migrasi ini mengganti namanya
 * tanpa memutus penugasan pengguna, lalu menyalin nilai kolom lama `users.role`
 * ke Spatie bagi pengguna yang belum punya peran.
 */
return new class extends Migration
{
    private array $map = [
        'superadmin' => 'super-admin',
        'admin'      => 'admin-opd',
    ];

    public function up(): void
    {
        $now = now();

        foreach ($this->map as $lama => $baru) {
            $this->rename($lama, $baru);

            if (! DB::table('roles')->where('name', $baru)->where('guard_name', 'web')->exists()) {
                DB::table('roles')->insert([
                    'name' => $baru, 'guard_name' => 'web', 'created_at' => $now, 'updated_at' => $now,
                ]);
            }
        }

        // Salin kolom users.role (jika ada) ke tabel Spatie
        if (Schema::hasColumn('users', 'role')) {
            $roleIds = DB::table('roles')->where('guard_name', 'web')->pluck('id', 'name');

            DB::table('users')
                ->whereNotExists(fn ($q) => $q->select(DB::raw(1))
                    ->from('model_has_roles')
                    ->whereColumn('model_has_roles.model_id', 'users.id')
                    ->where('model_has_roles.model_type', \App\Models\User::class))
                ->whereNotNull('role')
                ->get(['id', 'role'])
                ->each(function ($user) use ($roleIds) {
                    $nama = $this->map[$user->role] ?? $user->role;
                    if (isset($roleIds[$nama])) {
                        DB::table('model_has_roles')->insert([
                            'role_id'    => $roleIds[$nama],
                            'model_type' => \App\Models\User::class,
                            'model_id'   => $user->id,
                        ]);
                    }
                });
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        foreach ($this->map as $lama => $baru) {
            $this->rename($baru, $lama);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function rename(string $dari, string $ke): void
    {
        $sumber = DB::table('roles')->where('name', $dari)->where('guard_name', 'web')->first();
        if (! $sumber) {
            return;
        }

        $target = DB::table('roles')->where('name', $ke)->where('guard_name', 'web')->first();

        if (! $target) {
            DB::table('roles')->where('id', $sumber->id)->update(['name' => $ke, 'updated_at' => now()]);
            return;
        }

        // Kedua nama sudah ada: pindahkan penugasan ke role target, lalu hapus role lama
        $sudahAda = DB::table('model_has_roles')->where('role_id', $target->id)
            ->get(['model_type', 'model_id'])
            ->map(fn ($r) => $r->model_type . '#' . $r->model_id)
            ->all();

        DB::table('model_has_roles')->where('role_id', $sumber->id)->get()
            ->reject(fn ($r) => in_array($r->model_type . '#' . $r->model_id, $sudahAda, true))
            ->each(fn ($r) => DB::table('model_has_roles')->insert([
                'role_id' => $target->id, 'model_type' => $r->model_type, 'model_id' => $r->model_id,
            ]));

        DB::table('model_has_roles')->where('role_id', $sumber->id)->delete();
        DB::table('role_has_permissions')->where('role_id', $sumber->id)->delete();
        DB::table('roles')->where('id', $sumber->id)->delete();
    }
};
