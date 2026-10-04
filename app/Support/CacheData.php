<?php
namespace App\Support;

use Closure;
use Illuminate\Support\Facades\Cache;

/**
 * Cache untuk data UMKM yang sering dibaca (beranda, statistik, data peta).
 *
 * Setiap kunci memuat nomor versi; versi dinaikkan otomatis setiap kali data UMKM,
 * produk, pemasaran, legalitas, keuangan, atau profil berubah (lihat AppServiceProvider),
 * sehingga isi cache tidak pernah basi walau TTL-nya panjang.
 */
final class CacheData
{
    private const KUNCI_VERSI = 'data-umkm:versi';

    public static function ingat(string $kunci, int $detik, Closure $isi): mixed
    {
        return Cache::remember(self::kunci($kunci), $detik, $isi);
    }

    /** Batalkan seluruh cache data UMKM (dipanggil saat data berubah). */
    public static function segarkan(): void
    {
        Cache::forever(self::KUNCI_VERSI, self::versi() + 1);
    }

    private static function versi(): int
    {
        return (int) Cache::get(self::KUNCI_VERSI, 1);
    }

    private static function kunci(string $kunci): string
    {
        return 'data-umkm:v' . self::versi() . ':' . $kunci;
    }
}
