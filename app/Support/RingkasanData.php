<?php
namespace App\Support;

use App\Models\Umkm;

/**
 * Baris "Ringkasan data" yang sama persis di Beranda, Semua Brand, dan Tentang Kami:
 * total brand, jumlah UMKM per Sektor Usaha — hanya sektor yang sudah ada datanya,
 * angka sama dengan filter Peta Interaktif — lalu jumlah kabupaten/kota.
 */
final class RingkasanData
{
    /**
     * Di-cache; dibatalkan otomatis saat data UMKM berubah (lihat CacheData).
     *
     * @return list<array{value: int, label: string, highlight?: bool}>
     */
    public static function publik(): array
    {
        return CacheData::ingat('ringkasan-data', 600, function () {
            $agg = Umkm::aktif()->toBase()->selectRaw('
                count(*) as total,
                count(distinct case when kabupaten <> ? then kabupaten end) as kabupaten
            ', [Umkm::KABUPATEN_KOSONG])->first();

            $sektor = collect(TagUmkm::jumlah()['sektor'])
                ->filter(fn (int $n) => $n > 0)
                ->map(fn (int $n, string $kode) => ['value' => $n, 'label' => TagUmkm::FILTER['sektor']['opsi'][$kode]])
                ->values()
                ->all();

            return [
                ['value' => (int) $agg->total, 'label' => 'Brand UMKM', 'highlight' => true],
                ...$sektor,
                ['value' => (int) $agg->kabupaten, 'label' => 'Kabupaten/Kota'],
            ];
        });
    }
}
