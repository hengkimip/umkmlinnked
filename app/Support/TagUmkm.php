<?php
namespace App\Support;

use App\Models\Umkm;

/**
 * Pengelompokan UMKM untuk filter kotak centang Sektor Usaha, Platform Digital,
 * Jangkauan Pasar, dan Sertifikasi Produk — dipakai bersama oleh Peta Interaktif,
 * Semua Brand, Go Digital, dan Go Global agar hasil & angkanya selalu sama.
 *
 * Dalam satu grup UMKM cukup memenuhi salah satu pilihan; antar-grup harus memenuhi semuanya.
 */
final class TagUmkm
{
    public const FILTER = [
        'sektor' => ['judul' => 'Sektor Usaha', 'opsi' => Umkm::SEKTOR_LABEL],
        'platform' => ['judul' => 'Platform Digital', 'opsi' => [
            'tokopedia'   => 'Tokopedia',
            'shopee'      => 'Shopee',
            'tiktok'      => 'TikTok Shop',
            'instagram'   => 'Instagram',
            'facebook'    => 'Facebook',
            'whatsapp'    => 'WhatsApp Business',
            'website'     => 'Website Mandiri',
            'marketplace' => 'Marketplace Lainnya',
        ]],
        'jangkauan' => ['judul' => 'Jangkauan Pasar', 'opsi' => [
            'lokal'    => 'Lokal (Kabupaten/Kota)',
            'regional' => 'Regional (Antarkabupaten/Provinsi)',
            'nasional' => 'Nasional',
            'ekspor'   => 'Ekspor (Internasional)',
        ]],
        'sertifikasi' => ['judul' => 'Sertifikasi Produk', 'opsi' => [
            'halal'   => 'Halal',
            'bpom'    => 'BPOM',
            'pirt'    => 'PIRT',
            'sni'     => 'SNI',
            'hki'     => 'HKI/Merek Terdaftar',
            'nib'     => 'NIB (Nomor Induk Berusaha)',
            'organik' => 'Sertifikasi Organik',
            'lainnya' => 'Lainnya',
        ]],
    ];

    /** Kolom & relasi yang dibutuhkan untuk menghitung tag (dipakai juga oleh data peta). */
    public const KOLOM = ['id', 'sektor', 'whatsapp', 'website', 'instagram', 'facebook', 'tokopedia', 'shopee'];
    public const RELASI = [
        'pemasaran:id,umkm_id,platform_online,jangkauan_pasar',
        'legalitas:id,umkm_id,nomor_nib,nomor_halal,nomor_bpom,nomor_pirt,nomor_sni,sertifikat_lain',
        'profil:id,umkm_id,program_bi,saluran_pemasaran,marketplace,bentuk_legalitas,sertifikasi_produk',
    ];

    private const MARKETPLACE_LAIN = '/lazada|bukalapak|blibli|jd\.id|zalora|gofood|grabfood|shopeefood|amazon|alibaba|etsy/';

    // Host yang bukan "website mandiri" (media sosial, marketplace, tautan bio)
    private const BUKAN_WEBSITE = '/instagram|facebook|fb\.com|tiktok|tokopedia|shopee|lazada|bukalapak|blibli|wa\.me|whatsapp|linktr\.ee|bit\.ly|google\./';

    // Kata kunci sertifikasi pada teks kuesioner (setelah tanda "-" dibuang)
    private const SERTIFIKASI_KUNCI = [
        'halal'   => '/halal/',
        'bpom'    => '/bpom/',
        'pirt'    => '/\bp ?irt\b|spp ?irt/',
        'sni'     => '/\bsni\b/',
        'hki'     => '/\bhaki\b|\bhki\b|merek|merk|hak cipta|paten/',
        'nib'     => '/\bnib\b/',
        'organik' => '/organi[kc]/',
    ];

    private const SERTIFIKASI_KOLOM = [
        'halal' => 'nomor_halal', 'bpom' => 'nomor_bpom', 'pirt' => 'nomor_pirt',
        'sni' => 'nomor_sni', 'nib' => 'nomor_nib',
    ];

    /** Kode pilihan yang sah untuk satu grup. */
    public static function kode(string $grup): array
    {
        return array_keys(self::FILTER[$grup]['opsi'] ?? []);
    }

    /** Kode sektor database → kode tombol Sektor Usaha (kode lama mis. perikanan → pertanian). */
    public static function kodeSektor(?string $sektor): string
    {
        return Umkm::kodeSektor($sektor);
    }

    /**
     * Kode tombol filter yang dipenuhi UMKM, per grup — dari kolom terstruktur
     * (pemasaran, legalitas, tautan usaha) ditambah teks jawaban kuesioner profil.
     * Relasi pada self::RELASI harus sudah dimuat.
     *
     * @return array<string, list<string>>
     */
    public static function untuk(Umkm $u): array
    {
        $leg    = $u->legalitas;
        $profil = $u->profil;

        // --- Platform digital ---
        $tercatat = $u->pemasaran?->platform_online ?? [];
        $teks = strtolower(implode(' ', [
            $u->tokopedia, $u->shopee, $u->instagram, $u->facebook, $u->website,
            $profil?->saluran_pemasaran, $profil?->marketplace,
        ]));
        $ada  = fn (string $p) => in_array($p, $tercatat, true) || str_contains($teks, $p);
        $host = strtolower((string) parse_url((string) $u->websiteUrl(), PHP_URL_HOST));

        $platform = array_keys(array_filter([
            'tokopedia'   => $ada('tokopedia'),
            'shopee'      => $ada('shopee'),
            'tiktok'      => $ada('tiktok'),
            'instagram'   => $ada('instagram') || $u->instagramUrl() !== null,
            'facebook'    => $ada('facebook') || $u->facebookUrl() !== null,
            'whatsapp'    => $ada('whatsapp') || $u->wa_link !== null,
            'website'     => $host !== '' && ! preg_match(self::BUKAN_WEBSITE, $host),
            'marketplace' => (bool) preg_match(self::MARKETPLACE_LAIN, $teks),
        ]));

        // --- Sertifikasi produk ---
        $teksSert = trim(strtolower(str_replace('-', '', (string) $profil?->sertifikasi_produk)));
        $teksSemua = $teksSert . ' ' . strtolower((string) $profil?->bentuk_legalitas)
            . ' ' . strtolower(implode(' ', (array) ($leg?->sertifikat_lain ?? [])));

        $sertifikasi = [];
        foreach (self::SERTIFIKASI_KUNCI as $kode => $pola) {
            $kolom = self::SERTIFIKASI_KOLOM[$kode] ?? null;
            if (($kolom && filled($leg?->{$kolom})) || preg_match($pola, $teksSemua)) {
                $sertifikasi[] = $kode;
            }
        }

        // "Lainnya": sertifikat lain tercatat, atau jawaban sertifikasi yang tidak cocok tombol mana pun
        $cocokKunci = fn (string $t) => collect(self::SERTIFIKASI_KUNCI)->contains(fn ($p) => preg_match($p, $t));
        $jawabanLain = $teksSert !== ''
            && ! preg_match('/^(tidak|belum|tdk|blm|0|_|\.+)/', $teksSert)
            && ! $cocokKunci($teksSert);
        if (filled($leg?->sertifikat_lain) || $jawabanLain) {
            $sertifikasi[] = 'lainnya';
        }

        $jangkauan = $u->pemasaran?->jangkauan_pasar;

        return [
            'sektor'      => [self::kodeSektor($u->sektor)],
            'platform'    => $platform,
            'jangkauan'   => isset(self::FILTER['jangkauan']['opsi'][$jangkauan]) ? [$jangkauan] : [],
            'sertifikasi' => $sertifikasi,
        ];
    }

    /**
     * Tag seluruh UMKM aktif: [id => [grup => kode[]]]. Di-cache; dibatalkan otomatis saat data berubah.
     *
     * @return array<int, array<string, list<string>>>
     */
    public static function semua(): array
    {
        return CacheData::ingat('umkm:tag', 600, fn () => Umkm::aktif()
            ->select(self::KOLOM)
            ->with(self::RELASI)
            ->get()
            ->mapWithKeys(fn (Umkm $u) => [$u->id => self::untuk($u)])
            ->all());
    }

    /**
     * ID UMKM aktif yang cocok dengan pilihan filter: [grup => kode[]].
     *
     * @param  array<string, list<string>>  $pilihan
     * @return list<int>
     */
    public static function idCocok(array $pilihan): array
    {
        $pilihan = array_filter($pilihan);
        if (isset($pilihan['sektor'])) {
            $pilihan['sektor'] = array_map(self::kodeSektor(...), $pilihan['sektor']);
        }

        $ids = [];
        foreach (self::semua() as $id => $tag) {
            foreach ($pilihan as $grup => $kode) {
                if (! array_intersect($kode, $tag[$grup] ?? [])) {
                    continue 2;
                }
            }
            $ids[] = $id;
        }

        return $ids;
    }

    /**
     * Jumlah UMKM per pilihan, mis. ['platform' => ['shopee' => 36, ...], ...].
     *
     * @return array<string, array<string, int>>
     */
    public static function jumlah(): array
    {
        $hasil = array_map(fn ($def) => array_fill_keys(array_keys($def['opsi']), 0), self::FILTER);
        foreach (self::semua() as $tag) {
            foreach ($tag as $grup => $kode) {
                foreach ($kode as $k) {
                    $hasil[$grup][$k]++;
                }
            }
        }

        return $hasil;
    }
}
