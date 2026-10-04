<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('produk', function (Blueprint $table) {
            // Badge status/promosi: unggulan, terbaru, promo, terlaris, rekomendasi
            if (!Schema::hasColumn('produk', 'badge')) {
                $table->string('badge', 20)->nullable()->after('is_unggulan');
            }
        });

        // Produk unggulan lama ikut berbadge "unggulan" agar konsisten
        DB::table('produk')->where('is_unggulan', true)->whereNull('badge')->update(['badge' => 'unggulan']);
    }

    public function down(): void
    {
        Schema::table('produk', function (Blueprint $table) {
            if (Schema::hasColumn('produk', 'badge')) {
                $table->dropColumn('badge');
            }
        });
    }
};
