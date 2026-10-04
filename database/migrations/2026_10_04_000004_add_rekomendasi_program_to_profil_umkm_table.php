<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Program rekomendasi yang ditetapkan KPw BI untuk UMKM (daftar nama program).
     */
    public function up(): void
    {
        Schema::table('profil_umkm', function (Blueprint $table) {
            $table->json('rekomendasi_program')->nullable()->after('rencana_pembiayaan');
        });
    }

    public function down(): void
    {
        Schema::table('profil_umkm', function (Blueprint $table) {
            $table->dropColumn('rekomendasi_program');
        });
    }
};
