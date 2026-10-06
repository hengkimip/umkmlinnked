<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Jumlah maksimum akun Admin OPD aktif per OPD — ditentukan Super Admin
 * (1 = admin tidak digandakan).
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('opd', function (Blueprint $table) {
            $table->unsignedTinyInteger('maks_admin')->default(1)->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('opd', function (Blueprint $table) {
            $table->dropColumn('maks_admin');
        });
    }
};
