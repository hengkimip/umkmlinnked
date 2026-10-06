<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Antrean review "kemungkinan duplikat": data UMKM baru (import/manual) yang skor
 * kemiripannya dengan UMKM tersimpan ≥ ambang. Data baru belum dibuat sampai admin memutuskan.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('umkm_duplikat', function (Blueprint $table) {
            $table->id();
            // UMKM tersimpan yang paling mirip (pembanding)
            $table->foreignId('umkm_id')->constrained('umkm')->cascadeOnDelete();
            // OPD tujuan & pengunggah data baru
            $table->foreignId('opd_id')->constrained('opd');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('sumber', 10);              // impor | manual
            $table->json('data');                      // data baru dalam format kolom Kelola Profil
            $table->json('baris')->nullable();         // baris file asli (impor) untuk disimpan sebagai baru
            $table->unsignedTinyInteger('skor');
            $table->json('rincian');                   // sinyal → {bobot, dapat, nilai}
            $table->string('status', 20)->default('menunggu'); // menunggu | digabung | dibuat_baru
            $table->foreignId('diputuskan_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('diputuskan_pada')->nullable();
            $table->unsignedBigInteger('umkm_hasil_id')->nullable(); // UMKM yang diperbarui / dibuat
            $table->timestamps();

            $table->index(['status', 'opd_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('umkm_duplikat');
    }
};
