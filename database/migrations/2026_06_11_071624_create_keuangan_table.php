<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('keuangan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('umkm_id')->constrained('umkm')->onDelete('cascade');
            $table->year('tahun');
            $table->decimal('omzet_tahunan', 18, 2)->nullable();
            $table->decimal('laba_bersih', 18, 2)->nullable();
            $table->decimal('total_aset', 18, 2)->nullable();
            $table->decimal('total_modal', 18, 2)->nullable();
            $table->boolean('memiliki_pencatatan')->default(false);
            $table->enum('jenis_pencatatan', ['manual','aplikasi','akuntan'])->nullable();
            $table->boolean('sudah_audit')->default(false);
            $table->timestamps();
            $table->unique(['umkm_id','tahun']);
        });
    }
    public function down(): void { Schema::dropIfExists('keuangan'); }
};