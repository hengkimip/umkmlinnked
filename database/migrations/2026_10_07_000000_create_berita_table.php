<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Berita official yang ditulis Super Admin dan tampil di /berita.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('berita', function (Blueprint $table) {
            $table->id();
            $table->string('judul');
            $table->string('slug', 140)->unique();
            $table->string('ringkasan', 300)->nullable();
            $table->longText('isi');
            $table->string('gambar')->nullable();
            $table->string('status', 10)->default('draft')->index(); // draft | terbit
            $table->timestamp('terbit_pada')->nullable()->index();
            $table->foreignId('penulis_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('berita');
    }
};
