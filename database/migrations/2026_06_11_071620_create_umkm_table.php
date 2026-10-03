<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('umkm', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pemilik_usaha_id')
                  ->constrained('pemilik_usaha')->onDelete('restrict');
            $table->foreignId('opd_id')
                  ->nullable()->constrained('opd')->onDelete('set null');
            $table->string('nama_usaha')->index();
            $table->string('slug')->unique();
            $table->text('deskripsi')->nullable();
            $table->string('sektor')->index();
            $table->string('sub_sektor')->nullable();
            $table->string('kabupaten')->index();
            $table->string('kecamatan')->index();
            $table->string('kelurahan')->nullable();
            $table->text('alamat_usaha');
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->string('telepon', 20)->nullable();
            $table->string('whatsapp', 20)->nullable();
            $table->string('email')->nullable();
            $table->string('website')->nullable();
            $table->string('instagram')->nullable();
            $table->string('facebook')->nullable();
            $table->string('tokopedia')->nullable();
            $table->string('shopee')->nullable();
            $table->integer('jumlah_tenaga_kerja')->default(0);
            $table->year('tahun_berdiri')->nullable();
            $table->string('foto_usaha')->nullable();
            $table->integer('skor_legalitas')->default(0);
            $table->integer('skor_sertifikasi')->default(0);
            $table->integer('skor_produksi')->default(0);
            $table->integer('skor_pemasaran')->default(0);
            $table->integer('skor_keuangan')->default(0);
            $table->integer('skor_total')->default(0)->index();
            $table->enum('klasifikasi', ['dasar','berkembang','unggulan'])->default('dasar')->index();
            $table->enum('status', ['draft','aktif','nonaktif'])->default('draft')->index();
            $table->boolean('is_featured')->default(false);
            $table->timestamps();
            $table->softDeletes();
            $table->index(['sektor','klasifikasi','status']);
            $table->index(['kabupaten','kecamatan','status']);
        });
    }
    public function down(): void { Schema::dropIfExists('umkm'); }
};