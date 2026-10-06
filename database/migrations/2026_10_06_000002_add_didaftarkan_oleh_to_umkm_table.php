<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Pencatat pertama UMKM. OPD pembina (opd_id) ditetapkan saat UMKM pertama kali dimasukkan
 * dan tidak dapat dialihkan oleh OPD lain di kemudian hari.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('umkm', function (Blueprint $table) {
            $table->foreignId('didaftarkan_oleh')->nullable()->after('opd_id')->constrained('users')->nullOnDelete();
        });

        // Isi dari log aktivitas "created" bila tersedia (data lama hasil seeder tidak punya pencatat)
        if (Schema::hasTable('activity_log')) {
            DB::table('activity_log')
                ->where('subject_type', 'App\\Models\\Umkm')
                ->where('event', 'created')
                ->where('causer_type', 'App\\Models\\User')
                ->whereNotNull('causer_id')
                ->orderBy('id')
                ->get(['subject_id', 'causer_id'])
                ->filter(fn ($log) => DB::table('users')->where('id', $log->causer_id)->exists())
                ->each(fn ($log) => DB::table('umkm')->where('id', $log->subject_id)->whereNull('didaftarkan_oleh')
                    ->update(['didaftarkan_oleh' => $log->causer_id]));
        }
    }

    public function down(): void
    {
        Schema::table('umkm', function (Blueprint $table) {
            $table->dropConstrainedForeignId('didaftarkan_oleh');
        });
    }
};
