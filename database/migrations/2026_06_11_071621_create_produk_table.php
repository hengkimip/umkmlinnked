<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('produk', function (Blueprint $table) {
            $table->id();
            $table->foreignId('umkm_id')->constrained('umkm')->onDelete('cascade');
            $table->string('nama_produk');
            $table->text('deskripsi')->nullable();
            $table->decimal('harga', 15, 2)->nullable();
            $table->string('satuan', 30)->nullable();
            $table->integer('kapasitas_produksi')->nullable();
            $table->string('satuan_kapasitas', 20)->nullable();
            $table->string('foto')->nullable();
            $table->boolean('is_unggulan')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['umkm_id','is_active']);
        });
    }
    public function down(): void { Schema::dropIfExists('produk'); }
};