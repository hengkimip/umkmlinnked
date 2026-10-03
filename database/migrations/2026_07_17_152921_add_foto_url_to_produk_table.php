<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('produk', function (Blueprint $table) {
            // Tambah foto_url hanya jika belum ada
            if (!Schema::hasColumn('produk', 'foto_url')) {
                $table->text('foto_url')->nullable()->after('foto');
            }

            // Tambah urutan hanya jika belum ada
            if (!Schema::hasColumn('produk', 'urutan')) {
                $table->tinyInteger('urutan')->default(1)->after('foto_url');
            }
        });
    }

    public function down(): void
    {
        Schema::table('produk', function (Blueprint $table) {
            if (Schema::hasColumn('produk', 'foto_url')) {
                $table->dropColumn('foto_url');
            }
            if (Schema::hasColumn('produk', 'urutan')) {
                $table->dropColumn('urutan');
            }
        });
    }
};