<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('legalitas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('umkm_id')->constrained('umkm')->onDelete('cascade');
            $table->string('nomor_nib')->nullable()->index();
            $table->date('tanggal_nib')->nullable();
            $table->string('nomor_siup')->nullable();
            $table->date('tanggal_siup')->nullable();
            $table->string('nomor_tdp')->nullable();
            $table->string('nomor_npwp')->nullable();
            $table->string('nomor_akte')->nullable();
            $table->string('nomor_halal')->nullable();
            $table->date('expired_halal')->nullable();
            $table->string('nomor_bpom')->nullable();
            $table->date('expired_bpom')->nullable();
            $table->string('nomor_pirt')->nullable();
            $table->date('expired_pirt')->nullable();
            $table->string('nomor_sni')->nullable();
            $table->json('sertifikat_lain')->nullable();
            $table->string('file_nib')->nullable();
            $table->string('file_halal')->nullable();
            $table->string('file_bpom')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('legalitas'); }
};