<?php

use App\Models\Umkm;
use App\Support\CacheData;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Sektor usaha diringkas jadi 6: Kuliner, Fesyen/Wastra, Kerajinan,
 * Pertanian & Agroindustri, Jasa, Lainnya. Kode lama dipindah ke penggantinya
 * (Umkm::SEKTOR_LAMA); kode lain yang tidak dikenal menjadi "lainnya".
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (Umkm::SEKTOR_LAMA as $lama => $baru) {
            DB::table('umkm')->where('sektor', $lama)->update(['sektor' => $baru]);
        }

        DB::table('umkm')
            ->whereNotIn('sektor', array_keys(Umkm::SEKTOR_LABEL))
            ->update(['sektor' => 'lainnya']);

        // Update massal tidak memicu event model, jadi cache data dibatalkan manual
        CacheData::segarkan();
        Cache::forget('sektor_list');
    }

    public function down(): void
    {
        // Tidak dapat dikembalikan: sektor asal tidak disimpan
    }
};
