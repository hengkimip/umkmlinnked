<?php
namespace App\Support;

use App\Models\Umkm;
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
 */
final class FilterUmkm
{
    public const MAKS_KATA_KUNCI = 100;

    public const KLASIFIKASI = ['dasar', 'berkembang', 'unggulan'];
    public const PLATFORM    = ['tokopedia', 'shopee', 'instagram', 'whatsapp', 'tiktok'];
    public const JANGKAUAN   = ['lokal', 'regional', 'nasional', 'ekspor'];
    public const SERTIFIKASI = ['halal', 'bpom', 'pirt', 'sni'];

    /**
     * @return array{q?: string, sektor?: string, kabupaten?: string, klasifikasi?: string,
     *               harga_min?: int, harga_max?: int, platform?: string, jangkauan?: string, sertifikasi?: string}
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

    private static function validasi(Request $request): array
    {
        $teks  = fn (array $daftar) => ['string', Rule::in($daftar)];
        $harga = ['integer', 'min:0', 'max:999999999999'];

        $aturan = [
            'q'           => ['string', 'max:1000'],
            'sektor'      => $teks(array_keys(Umkm::SEKTOR_LABEL)),
            'kabupaten'   => $teks([...array_keys(Umkm::KABUPATEN_LENGKAP), Umkm::KABUPATEN_KOSONG]),
            'klasifikasi' => $teks(self::KLASIFIKASI),
            'harga_min'   => $harga,
            'harga_max'   => $harga,
            'platform'    => $teks(self::PLATFORM),
            'jangkauan'   => $teks(self::JANGKAUAN),
            'sertifikasi' => $teks(self::SERTIFIKASI),
        ];

        $valid = array_intersect_key(Validator::make($request->query(), $aturan)->valid(), $aturan);

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
