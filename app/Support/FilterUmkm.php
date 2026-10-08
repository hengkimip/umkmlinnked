<?php
namespace App\Support;

use App\Models\Umkm;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * Validasi parameter filter halaman publik (Semua Brand, Go Digital, Go Global).
 *
 * Setiap parameter dicocokkan dengan daftar nilai yang diizinkan (whitelist).
 * Nilai yang tidak valid — termasuk array seperti ?sektor[]=x — dibuang diam-diam
 * agar pengunjung tidak pernah melihat halaman galat, dan tidak ada nilai mentah
 * yang sampai ke query.
 *
 * Sektor, platform, jangkauan & sertifikasi boleh berisi beberapa pilihan dipisah koma
 * (?platform=shopee,tiktok); pilihannya sama dengan filter Peta Interaktif (TagUmkm).
 */
final class FilterUmkm
{
    public const MAKS_KATA_KUNCI = 100;

    public const KLASIFIKASI = ['dasar', 'berkembang', 'unggulan'];

    /** Filter kotak centang (boleh banyak pilihan) — dicocokkan lewat TagUmkm. */
    public const GRUP_TAG = ['sektor', 'platform', 'jangkauan', 'sertifikasi'];

    /**
     * @return array{q?: string, sektor?: string, kabupaten?: string, klasifikasi?: string,
     *               harga_min?: int, harga_max?: int, platform?: string, jangkauan?: string, sertifikasi?: string}
     *         (sektor/platform/jangkauan/sertifikasi: satu atau beberapa kode dipisah koma)
     */
    public static function dari(Request $request): array
    {
        // Dihitung sekali per request (dipakai controller dan view)
        return $request->attributes->get('filter_umkm')
            ?? tap(self::validasi($request), fn ($f) => $request->attributes->set('filter_umkm', $f));
    }

    /**
     * Nilai filter tervalidasi untuk mengisi ulang form di view (null bila tidak ada/tidak valid).
     */
    public static function nilai(string $kunci): ?string
    {
        $nilai = self::dari(request())[$kunci] ?? null;

        return $nilai === null ? null : (string) $nilai;
    }

    /**
     * Daftar pilihan untuk filter banyak-pilihan, mis. daftar('platform') → ['shopee', 'tiktok'].
     *
     * @return list<string>
     */
    public static function daftar(string $kunci): array
    {
        $nilai = self::nilai($kunci);

        return $nilai === null ? [] : explode(',', $nilai);
    }

    /**
     * Terapkan filter sektor/platform/jangkauan/sertifikasi ke query UMKM — hasilnya sama
     * dengan filter Peta Interaktif. Tidak ada nilai input yang menjadi nama kolom.
     */
    public static function terapkanTag(Builder $query, array $f): void
    {
        $pilihan = [];
        foreach (self::GRUP_TAG as $grup) {
            if (isset($f[$grup])) {
                $pilihan[$grup] = explode(',', $f[$grup]);
            }
        }

        if ($pilihan) {
            $query->whereIn($query->getModel()->qualifyColumn('id'), TagUmkm::idCocok($pilihan));
        }
    }

    private static function validasi(Request $request): array
    {
        $teks  = fn (array $daftar) => ['string', Rule::in($daftar)];
        $harga = ['integer', 'min:0', 'max:999999999999'];

        $aturan = [
            'q'           => ['string', 'max:1000'],
            'kabupaten'   => $teks([...array_keys(Umkm::KABUPATEN_LENGKAP), Umkm::KABUPATEN_KOSONG]),
            'klasifikasi' => $teks(self::KLASIFIKASI),
            'harga_min'   => $harga,
            'harga_max'   => $harga,
        ];

        $valid = array_intersect_key(Validator::make($request->query(), $aturan)->valid(), $aturan);

        // Banyak pilihan dipisah koma; tiap pilihan harus ada di daftar (urutan mengikuti daftar)
        foreach (self::GRUP_TAG as $grup) {
            $mentah = $request->query($grup);
            if (! is_string($mentah) || strlen($mentah) > 300) {
                continue;
            }
            $izin = TagUmkm::kode($grup);
            if ($grup === 'sektor') {
                // Kode sektor lama (mis. "perikanan" dari tautan lama) tetap diterima
                $izin = array_values(array_unique([...$izin, ...array_keys(Umkm::SEKTOR_LAMA)]));
            }
            $dipilih = array_map(fn ($v) => strtolower(trim($v)), explode(',', $mentah));
            $daftar  = array_values(array_intersect($izin, $dipilih));
            if ($daftar) {
                $valid[$grup] = implode(',', $daftar);
            }
        }

        if (isset($valid['q'])) {
            $valid['q'] = mb_substr(trim(preg_replace('/\s+/u', ' ', $valid['q'])), 0, self::MAKS_KATA_KUNCI);
        }
        foreach (['harga_min', 'harga_max'] as $k) {
            if (isset($valid[$k])) $valid[$k] = (int) $valid[$k];
        }

        return array_filter($valid, fn ($v) => $v !== '' && $v !== null);
    }

    /**
     * Pola LIKE "%kata%" dengan wildcard pengguna (% _ \) di-escape,
     * sehingga kata kunci selalu dicari sebagai teks biasa.
     */
    public static function like(string $kata): string
    {
        return '%' . addcslashes($kata, '%_\\') . '%';
    }
}
