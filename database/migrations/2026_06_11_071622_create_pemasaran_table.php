<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('pemasaran', function (Blueprint $table) {
            $table->id();
            $table->foreignId('umkm_id')->constrained('umkm')->onDelete('cascade');
            $table->json('platform_online')->nullable();
            $table->boolean('memiliki_website')->default(false);
            $table->string('jangkauan_pasar')->nullable();
            $table->json('provinsi_tujuan')->nullable();
            $table->json('negara_ekspor')->nullable();
            $table->string('strategi_pemasaran')->nullable();
            $table->integer('jumlah_reseller')->default(0);
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('pemasaran'); }
};