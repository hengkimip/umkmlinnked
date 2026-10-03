<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PRD v1.1 §6: tabel users tanpa kolom `role`; peran sepenuhnya di Spatie.
 * Nilai kolom lama sudah disalin ke Spatie oleh migrasi
 * 2026_10_04_000000_normalize_spatie_role_names.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'role')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['role']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'role')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['admin', 'superadmin'])->default('admin')->after('email')->index();
        });
    }
};
