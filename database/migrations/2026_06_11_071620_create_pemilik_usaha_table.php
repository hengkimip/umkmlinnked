<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('pemilik_usaha', function (Blueprint $table) {
            $table->id();
            $table->string('nama_lengkap');
            $table->string('nik', 16)->unique()->index();
            $table->string('jenis_kelamin', 10);
            $table->date('tanggal_lahir')->nullable();
            $table->text('alamat');
            $table->string('kabupaten')->index();
            $table->string('kecamatan')->index();
            $table->string('telepon', 20)->index();
            $table->string('email')->nullable();
            $table->string('pendidikan')->nullable();
            $table->string('foto_ktp')->nullable();
            $table->string('foto_profil')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }
    public function down(): void { Schema::dropIfExists('pemilik_usaha'); }
};