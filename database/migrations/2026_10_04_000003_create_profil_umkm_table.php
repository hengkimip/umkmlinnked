<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Jawaban kuesioner profil UMKM yang belum memiliki kolom terstruktur
     * (sebelumnya hanya diturunkan menjadi flag saat import lalu dibuang).
     */
    public function up(): void
    {
        Schema::create('profil_umkm', function (Blueprint $table) {
            $table->id();
            $table->foreignId('umkm_id')->unique()->constrained('umkm')->cascadeOnDelete();
            $table->text('program_bi')->nullable();
            $table->text('produk_lainnya')->nullable();
            $table->text('kapasitas_produk_lainnya')->nullable();
            $table->text('saluran_pemasaran')->nullable();
            $table->text('marketplace')->nullable();
            $table->text('bentuk_legalitas')->nullable();
            $table->text('sertifikasi_produk')->nullable();
            $table->text('metode_pencatatan')->nullable();
            $table->string('pembiayaan_2026')->nullable();
            $table->text('pembiayaan_diterima')->nullable();
            $table->text('rencana_pembiayaan')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profil_umkm');
    }
};
