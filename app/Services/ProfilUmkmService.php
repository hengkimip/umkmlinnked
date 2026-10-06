<?php
namespace App\Services;

use App\Models\Keuangan;
use App\Models\PemilikUsaha;
use App\Models\Produk;
use App\Models\Umkm;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Kolom-kolom formulir profil UMKM dan tempat penyimpanannya di database.
 * Dipakai halaman "Kelola Profil UMKM" (ubah/hapus per kolom) dan import Excel.
 */
class ProfilUmkmService
{
    public const BAGIAN = [
        'pemilik'   => 'Data pemilik usaha',
        'usaha'     => 'Data usaha',
        'produksi'  => 'Produk & kapasitas produksi',
        'pemasaran' => 'Pemasaran & kanal digital',
        'legalitas' => 'Legalitas & sertifikasi',
        'keuangan'  => 'Keuangan & pembiayaan',
        'rekomendasi' => 'Rekomendasi Program KPw BI',
    ];

    public const MAKS_PROGRAM = 20;

    public const JANGKAUAN = [
        'lokal' => 'Lokal (kabupaten/kota)', 'regional' => 'Regional (provinsi)',
        'nasional' => 'Nasional', 'ekspor' => 'Ekspor / internasional',
    ];

    private const TELEPON = 'regex:/^\+?[0-9\s().-]{8,20}$/';

    public function __construct(private UmkmScoringService $scoring) {}

    /**
     * Definisi kolom: label (sesuai formulir), bagian, tipe input, wajib, aturan validasi.
     */
    public static function kolom(): array
    {
        $teks = fn (int $max = 2000) => ['nullable', 'string', "max:{$max}"];

        return [
            // --- Pemilik ---
            'pemilik_nama'     => ['label' => 'Nama Pemilik Usaha', 'bagian' => 'pemilik', 'tipe' => 'text', 'wajib' => true, 'aturan' => ['string', 'max:255']],
            'pemilik_alamat'   => ['label' => 'Alamat Lengkap', 'bagian' => 'pemilik', 'tipe' => 'textarea', 'wajib' => true, 'aturan' => ['string', 'max:1000']],
            'pemilik_whatsapp' => ['label' => 'No Whatsapp', 'bagian' => 'pemilik', 'tipe' => 'tel', 'wajib' => true, 'aturan' => [self::TELEPON]],
            'pemilik_email'    => ['label' => 'Alamat E-Mail', 'bagian' => 'pemilik', 'tipe' => 'email', 'aturan' => ['nullable', 'email', 'max:255']],
            'program_bi'       => ['label' => 'Program yang pernah diikuti dari Bank Indonesia', 'bagian' => 'pemilik', 'tipe' => 'textarea', 'aturan' => $teks()],

            // --- Usaha ---
            'nama_usaha'       => ['label' => 'Nama UMKM/Usaha', 'bagian' => 'usaha', 'tipe' => 'text', 'wajib' => true, 'aturan' => ['string', 'max:255']],
            'alamat_usaha'     => ['label' => 'Alamat Usaha', 'bagian' => 'usaha', 'tipe' => 'textarea', 'wajib' => true, 'aturan' => ['string', 'max:1000'],
                                   'bantuan' => 'Kabupaten/kota diperbarui otomatis bila nama kabupaten disebut di alamat.'],
            'kabupaten'        => ['label' => 'Kota/Kabupaten', 'bagian' => 'usaha', 'tipe' => 'select', 'wajib' => true,
                                   'opsi' => Umkm::KABUPATEN_LENGKAP, 'aturan' => [Rule::in(array_keys(Umkm::KABUPATEN_LENGKAP))],
                                   'bantuan' => 'Wilayah UMKM di peta interaktif & filter. Ikut diperbarui bila nama kota/kabupaten disebut di alamat usaha.'],
            'tahun_berdiri'    => ['label' => 'Tahun Berdirinya Usaha', 'bagian' => 'usaha', 'tipe' => 'number', 'aturan' => ['nullable', 'integer', 'min:1900', 'max:' . date('Y')]],
            'sektor'           => ['label' => 'Sektor Usaha', 'bagian' => 'usaha', 'tipe' => 'select', 'wajib' => true, 'opsi' => Umkm::SEKTOR_LABEL, 'aturan' => [Rule::in(array_keys(Umkm::SEKTOR_LABEL))]],
            'jumlah_karyawan'  => ['label' => 'Jumlah Karyawan', 'bagian' => 'usaha', 'tipe' => 'number', 'aturan' => ['nullable', 'integer', 'min:0', 'max:1000000']],

            // --- Produksi ---
            'produk_unggulan'          => ['label' => 'Produk / Jasa unggulan', 'bagian' => 'produksi', 'tipe' => 'text', 'wajib' => true, 'aturan' => ['string', 'max:255']],
            'kapasitas_produksi'       => ['label' => 'Kapasitas produksi per bulan (Pcs/Kg)', 'bagian' => 'produksi', 'tipe' => 'number', 'butuhProduk' => true, 'aturan' => ['nullable', 'integer', 'min:0', 'max:2000000000']],
            'produk_lainnya'           => ['label' => 'Apakah memiliki jenis produk lainnya ? mohon disebutkan secara spesifik.', 'bagian' => 'produksi', 'tipe' => 'textarea', 'aturan' => $teks()],
            'kapasitas_produk_lainnya' => ['label' => 'Berapa kapasitas produksi produk tersebut ?', 'bagian' => 'produksi', 'tipe' => 'textarea', 'aturan' => $teks()],
            'foto_url'                 => ['label' => 'foto_url', 'bagian' => 'produksi', 'tipe' => 'url', 'butuhProduk' => true, 'aturan' => ['nullable', 'url:http,https', 'max:2000'],
                                           'bantuan' => 'Tautan foto produk unggulan (mis. Google Drive). Foto hasil upload tetap diutamakan.'],

            // --- Pemasaran ---
            'saluran_pemasaran' => ['label' => 'Saluran pemasaran produk selama ini ?', 'bagian' => 'pemasaran', 'tipe' => 'textarea', 'aturan' => $teks()],
            'jangkauan_pasar'   => ['label' => 'Jangkauan pasar utama saat ini ?', 'bagian' => 'pemasaran', 'tipe' => 'select', 'opsi' => self::JANGKAUAN, 'aturan' => ['nullable', Rule::in(array_keys(self::JANGKAUAN))]],
            'wa_bisnis'         => ['label' => 'Nomor / Link WA bisnis', 'bagian' => 'pemasaran', 'tipe' => 'tel', 'aturan' => ['nullable', 'string', 'max:255']],
            'instagram'         => ['label' => 'URL/Link Instagram usaha / Facebook', 'bagian' => 'pemasaran', 'tipe' => 'text', 'aturan' => $teks(255)],
            'marketplace'       => ['label' => 'URL/Link marketplace usaha yang dimiliki :', 'bagian' => 'pemasaran', 'tipe' => 'textarea', 'aturan' => $teks()],
            'website'           => ['label' => 'URL/ Link Website :', 'bagian' => 'pemasaran', 'tipe' => 'text', 'aturan' => $teks(255)],

            // --- Legalitas ---
            'bentuk_legalitas'   => ['label' => 'Bentuk legalitas usaha yang dimiliki :', 'bagian' => 'legalitas', 'tipe' => 'textarea', 'aturan' => $teks(1000),
                                     'bantuan' => 'Sebutkan mis. NIB, SIUP, NPWP, Akta — dipakai untuk skor legalitas.'],
            'sertifikasi_produk' => ['label' => 'Sertifikasi produk yang dimiliki :', 'bagian' => 'legalitas', 'tipe' => 'textarea', 'aturan' => $teks(1000),
                                     'bantuan' => 'Sebutkan mis. Halal, BPOM, PIRT, SNI — dipakai untuk skor sertifikasi.'],

            // --- Keuangan ---
            'metode_pencatatan'   => ['label' => 'Bagaimana metode pencatatan keuangan usaha Anda saat ini ?', 'bagian' => 'keuangan', 'tipe' => 'textarea', 'aturan' => $teks(1000)],
            'omzet_bulanan'       => ['label' => 'Berapa rata-rata omzet usaha Anda perbulan :', 'bagian' => 'keuangan', 'tipe' => 'rupiah', 'aturan' => ['nullable', 'numeric', 'min:0', 'max:999999999999']],
            'pembiayaan_2026'     => ['label' => 'Apakah pada tahun 2026 sudah mendapatkan pembiayaan dari lembaga keuangan perbankan dan atau non perbankan ?', 'bagian' => 'keuangan', 'tipe' => 'text', 'saran' => ['Sudah', 'Belum'], 'aturan' => $teks(255)],
            'pembiayaan_diterima' => ['label' => 'Jika sudah mendapatkan akses pembiayaan sebutkan nama lembaga keuangan dan jumlah plafond yang diterima', 'bagian' => 'keuangan', 'tipe' => 'textarea', 'aturan' => $teks()],
            'rencana_pembiayaan'  => ['label' => 'Jika ada rencana akses pembiayaan sebutkan nama lembaga keuangan dan jumlah plafond yang akan diajukan', 'bagian' => 'keuangan', 'tipe' => 'textarea', 'aturan' => $teks()],

            // --- Rekomendasi (daftar program, tampil di halaman detail peta interaktif) ---
            'rekomendasi_program' => ['label' => 'Rekomendasi Program KPw BI', 'bagian' => 'rekomendasi', 'tipe' => 'program',
                                      'aturan' => ['nullable', 'array', 'max:' . self::MAKS_PROGRAM],
                                      'aturanItem' => ['string', 'max:150'],
                                      'bantuan' => 'Boleh lebih dari satu program. Tampil sebagai program yang ditetapkan KPw BI di halaman detail peta interaktif.'],
        ];
    }

    /**
     * Nama lembaga pada label program: Super Admin → Bank Indonesia / KPw BI,
     * Admin OPD → nama OPD-nya (mis. "Dinas Koperasi Kota Pontianak").
     *
     * @return array{program: string, rekomendasi: string}
     */
    public static function lembaga(?User $user): array
    {
        if (! $user || $user->isSuperAdmin()) {
            return ['program' => 'Bank Indonesia', 'rekomendasi' => 'KPw BI'];
        }

        $opd = $user->opd?->nama_opd ?: 'OPD';

        return ['program' => $opd, 'rekomendasi' => $opd];
    }

    /** kolom() dengan label program sesuai lembaga pengguna (lihat lembaga()). */
    public static function kolomUntuk(?User $user): array
    {
        $l     = self::lembaga($user);
        $kolom = self::kolom();

        $kolom['program_bi']['label']           = "Program yang pernah diikuti dari {$l['program']}";
        $kolom['rekomendasi_program']['label']   = "Rekomendasi Program {$l['rekomendasi']}";
        $kolom['rekomendasi_program']['bantuan'] = "Boleh lebih dari satu program. Program yang ditetapkan {$l['rekomendasi']} untuk UMKM ini.";

        return $kolom;
    }

    /** BAGIAN dengan judul bagian rekomendasi sesuai lembaga pengguna. */
    public static function bagianUntuk(?User $user): array
    {
        return array_replace(self::BAGIAN, ['rekomendasi' => 'Rekomendasi Program ' . self::lembaga($user)['rekomendasi']]);
    }

    /**
     * Nilai saat ini untuk seluruh kolom.
     */
    public function nilai(Umkm $umkm): array
    {
        $umkm->loadMissing(['pemilik', 'profil', 'pemasaran', 'legalitas', 'keuanganTerakhir']);
        $pemilik = $umkm->pemilik;
        $profil  = $umkm->profil;
        $leg     = $umkm->legalitas;
        $produk  = $umkm->produkUtama();
        $omzet   = $umkm->keuanganTerakhir?->omzet_tahunan;

        return [
            'pemilik_nama'     => $pemilik?->nama_lengkap,
            'pemilik_alamat'   => $pemilik?->alamat,
            'pemilik_whatsapp' => $pemilik?->telepon,
            'pemilik_email'    => $pemilik?->email,
            'program_bi'       => $profil?->program_bi,

            'nama_usaha'       => $umkm->nama_usaha,
            'alamat_usaha'     => $umkm->alamat_usaha,
            'kabupaten'        => $umkm->kabupaten,
            'tahun_berdiri'    => $umkm->tahun_berdiri,
            'sektor'           => $umkm->sektor,
            'jumlah_karyawan'  => $umkm->jumlah_tenaga_kerja,

            'produk_unggulan'          => $produk?->nama_produk,
            'kapasitas_produksi'       => $produk?->kapasitas_produksi,
            'produk_lainnya'           => $profil?->produk_lainnya,
            'kapasitas_produk_lainnya' => $profil?->kapasitas_produk_lainnya,
            'foto_url'                 => $produk?->foto_url,

            // Data lama (sebelum tabel profil ada) ditampilkan dari kolom turunannya
            'saluran_pemasaran' => $profil?->saluran_pemasaran ?? (implode(', ', array_map('ucfirst', $umkm->pemasaran?->platform_online ?? [])) ?: null),
            'jangkauan_pasar'   => $umkm->pemasaran?->jangkauan_pasar,
            'wa_bisnis'         => $umkm->whatsapp,
            'instagram'         => $umkm->instagram,
            'marketplace'       => $profil?->marketplace ?? (implode("\n", array_unique(array_filter([$umkm->tokopedia, $umkm->shopee]))) ?: null),
            'website'           => $umkm->website,

            'bentuk_legalitas'   => $profil?->bentuk_legalitas ?? $this->daftarAda($leg, ['nomor_nib' => 'NIB', 'nomor_siup' => 'SIUP', 'nomor_npwp' => 'NPWP', 'nomor_akte' => 'Akta']),
            'sertifikasi_produk' => $profil?->sertifikasi_produk ?? $this->daftarAda($leg, ['nomor_halal' => 'Halal', 'nomor_bpom' => 'BPOM', 'nomor_pirt' => 'PIRT', 'nomor_sni' => 'SNI']),

            'metode_pencatatan'   => $profil?->metode_pencatatan,
            'omzet_bulanan'       => $omzet !== null ? round((float) $omzet / 12) : null,
            'pembiayaan_2026'     => $profil?->pembiayaan_2026,
            'pembiayaan_diterima' => $profil?->pembiayaan_diterima,
            'rencana_pembiayaan'  => $profil?->rencana_pembiayaan,

            'rekomendasi_program' => $profil?->rekomendasi_program ?: [],
        ];
    }

    /**
     * Simpan satu kolom. $nilai null/kosong = hapus isi kolom.
     *
     * @throws ValidationException
     */
    public function simpan(Umkm $umkm, string $kolom, mixed $nilai): void
    {
        $def = self::kolom()[$kolom] ?? throw ValidationException::withMessages(['kolom' => 'Kolom tidak dikenal.']);

        $nilai = self::rapikan($nilai);

        Validator::make(['nilai' => $nilai], array_filter([
            'nilai'   => self::aturan($def),
            'nilai.*' => $def['aturanItem'] ?? null,
        ]), [
            'nilai.required' => "{$def['label']} wajib diisi dan tidak dapat dihapus.",
            'nilai.regex'    => 'Nomor telepon tidak valid (8–20 digit).',
            'nilai.array'    => "{$def['label']} harus berupa daftar.",
            'nilai.max'      => $def['tipe'] === 'program' ? 'Maksimal ' . self::MAKS_PROGRAM . ' program.' : null,
            'nilai.*.string' => 'Nama program harus berupa teks.',
            'nilai.*.max'    => 'Nama program maksimal 150 karakter.',
        ], ['nilai' => $def['label']])->validate();

        $produk = $umkm->produkUtama();
        if (! empty($def['butuhProduk']) && ! $produk) {
            throw ValidationException::withMessages(['nilai' => 'Isi "Produk / Jasa unggulan" terlebih dahulu.']);
        }

        DB::transaction(function () use ($umkm, $kolom, $nilai, $produk) {
            $this->terapkan($umkm, $kolom, $nilai, $produk);
            $this->scoring->simpan($umkm->fresh());
        });
    }

    /**
     * Tambah UMKM baru secara manual dari seluruh kolom profil (halaman Import Data).
     * Data masuk ke tabel yang sama dengan import & "Kelola Profil UMKM", lalu skor dihitung.
     * Pemilik dengan nomor WhatsApp yang sudah terdaftar dipakai ulang (tidak digandakan, datanya tidak diubah).
     *
     * Bila $cekDuplikat dan skor kemiripan dengan UMKM tersimpan ≥ ambang (DeteksiDuplikatService),
     * UMKM TIDAK dibuat: data masuk antrean review dan 'umkm' bernilai null.
     *
     * @return array{umkm: ?Umkm, pemilikTerdaftar: bool, antrean: ?\App\Models\UmkmDuplikat}
     *
     * @throws ValidationException
     */
    public function buat(array $input, int $opdId, ?User $user = null, bool $cekDuplikat = true): array
    {
        $kolom = self::kolom();
        $data  = [];
        $rules = [];
        $label = [];
        foreach ($kolom as $k => $def) {
            $data[$k]  = self::rapikan($input[$k] ?? null);
            $rules[$k] = self::aturan($def);
            if (isset($def['aturanItem'])) {
                $rules["{$k}.*"] = $def['aturanItem'];
            }
            $label[$k] = rtrim($def['label'], ' :?');
        }

        Validator::make($data, $rules, [
            'regex'                     => ':attribute tidak valid (8–20 digit).',
            'rekomendasi_program.max'   => 'Maksimal ' . self::MAKS_PROGRAM . ' program.',
            'rekomendasi_program.*.max' => 'Nama program maksimal 150 karakter.',
        ], $label)->validate();

        // Kolom yang dipakai saat membuat baris UMKM & pemilik; sisanya lewat terapkan()
        $dasar   = ['pemilik_nama', 'pemilik_alamat', 'pemilik_whatsapp', 'pemilik_email', 'nama_usaha', 'alamat_usaha', 'kabupaten', 'sektor'];
        $telepon = self::bersihkanTelepon($data['pemilik_whatsapp']);

        // Kemungkinan duplikat (skor kemiripan ≥ ambang) → antrean review, belum disimpan
        if ($cekDuplikat) {
            $deteksi = app(DeteksiDuplikatService::class);
            if ($hasil = $deteksi->periksa($data)) {
                return ['umkm' => null, 'pemilikTerdaftar' => false, 'antrean' => $deteksi->antrekan($data, $hasil, $opdId, $user, 'manual')];
            }
        }

        return DB::transaction(function () use ($data, $kolom, $dasar, $telepon, $opdId) {
            $pemilik = PemilikUsaha::firstOrCreate(['telepon' => $telepon], [
                'nama_lengkap'  => $data['pemilik_nama'],
                'nik'           => 'NIK-' . Str::random(10),
                'jenis_kelamin' => 'L',
                'alamat'        => $data['pemilik_alamat'],
                'kabupaten'     => Umkm::tebakKabupaten($data['pemilik_alamat']) ?? Umkm::KABUPATEN_KOSONG,
                'kecamatan'     => '-',
                'email'         => $data['pemilik_email'],
            ]);

            $umkm = Umkm::create([
                'pemilik_usaha_id' => $pemilik->id,
                'opd_id'           => $opdId,
                'nama_usaha'       => $data['nama_usaha'],
                'slug'             => Str::slug($data['nama_usaha']) . '-' . Str::random(5),
                'sektor'           => $data['sektor'],
                'kabupaten'        => $data['kabupaten'], // dipilih langsung di formulir
                'kecamatan'        => '-',
                'alamat_usaha'     => $data['alamat_usaha'],
                'whatsapp'         => $telepon, // WA bisnis default = WA pemilik (sama dengan import)
                'status'           => 'aktif',
            ]);

            // Urutan kolom() menjamin produk unggulan dibuat sebelum kapasitas & foto_url
            foreach ($kolom as $k => $def) {
                if (! in_array($k, $dasar, true) && $data[$k] !== null) {
                    $this->terapkan($umkm, $k, $data[$k], $umkm->produkUtama());
                }
            }

            // Baris pendamping selalu ada, sama seperti hasil import
            $umkm->legalitas()->firstOrCreate([]);
            $umkm->pemasaran()->firstOrCreate([]);
            $umkm->profil()->firstOrCreate([]);
            $this->keuangan($umkm);

            $this->scoring->simpan($umkm->fresh());

            return ['umkm' => $umkm->fresh(), 'pemilikTerdaftar' => ! $pemilik->wasRecentlyCreated, 'antrean' => null];
        });
    }

    /** Rapikan nilai masukan: trim teks; daftar dibersihkan dari kosong & duplikat. Kosong → null. */
    private static function rapikan(mixed $nilai): mixed
    {
        $nilai = match (true) {
            is_string($nilai) => trim($nilai),
            // Daftar: rapikan spasi, buang yang kosong & duplikat (tanpa membedakan huruf besar)
            is_array($nilai)  => array_values(collect($nilai)
                ->map(fn ($v) => is_string($v) ? preg_replace('/\s+/u', ' ', trim($v)) : $v)
                ->filter(fn ($v) => $v !== '' && $v !== null)
                ->unique(fn ($v) => is_string($v) ? mb_strtolower($v) : $v)
                ->all()),
            default           => $nilai,
        };

        return $nilai === '' || $nilai === [] ? null : $nilai;
    }

    private static function aturan(array $def): array
    {
        return array_merge(! empty($def['wajib']) ? ['required'] : [], $def['aturan']);
    }

    private function terapkan(Umkm $umkm, string $kolom, mixed $nilai, ?Produk $produk): void
    {
        $pemilik = $umkm->pemilik;

        switch ($kolom) {
            case 'pemilik_nama':
                $pemilik->update(['nama_lengkap' => $nilai]);
                break;
            case 'pemilik_alamat':
                $pemilik->update(['alamat' => $nilai, 'kabupaten' => Umkm::tebakKabupaten($nilai) ?? $pemilik->kabupaten]);
                break;
            case 'pemilik_whatsapp':
                $pemilik->update(['telepon' => self::bersihkanTelepon($nilai)]);
                break;
            case 'pemilik_email':
                $pemilik->update(['email' => $nilai]);
                break;

            case 'nama_usaha':
                $umkm->update(['nama_usaha' => $nilai]); // slug tetap agar tautan publik tidak putus
                break;
            case 'alamat_usaha':
                $umkm->update(['alamat_usaha' => $nilai, 'kabupaten' => Umkm::tebakKabupaten($nilai) ?? $umkm->kabupaten]);
                break;
            case 'kabupaten':
                $umkm->update(['kabupaten' => $nilai]);
                break;
            case 'tahun_berdiri':
                $umkm->update(['tahun_berdiri' => $nilai]);
                break;
            case 'sektor':
                $umkm->update(['sektor' => $nilai]);
                break;
            case 'jumlah_karyawan':
                $umkm->update(['jumlah_tenaga_kerja' => (int) $nilai]);
                break;

            case 'produk_unggulan':
                $this->simpanProdukUnggulan($umkm, $produk, $nilai);
                break;
            case 'kapasitas_produksi':
                $produk->update(['kapasitas_produksi' => $nilai, 'satuan_kapasitas' => $nilai !== null ? 'bulan' : null]);
                break;
            case 'foto_url':
                $produk->update(['foto_url' => $nilai]);
                break;

            case 'saluran_pemasaran':
                $this->simpanProfil($umkm, $kolom, $nilai);
                $umkm->pemasaran()->firstOrCreate([])->update(['platform_online' => self::platformDari($nilai)]);
                break;
            case 'jangkauan_pasar':
                $umkm->pemasaran()->firstOrCreate([])->update(['jangkauan_pasar' => $nilai]);
                break;
            case 'wa_bisnis':
                $umkm->update(['whatsapp' => self::bersihkanTelepon($nilai) ?: null]);
                break;
            case 'instagram':
                $umkm->update(['instagram' => $nilai]);
                break;
            case 'marketplace':
                $this->simpanProfil($umkm, $kolom, $nilai);
                $umkm->update([
                    'tokopedia' => self::marketplaceDari($nilai, 'tokopedia'),
                    'shopee'    => self::marketplaceDari($nilai, 'shopee'),
                ]);
                break;
            case 'website':
                $umkm->update(['website' => $nilai]);
                $umkm->pemasaran()->firstOrCreate([])->update(['memiliki_website' => $nilai !== null]);
                break;

            case 'bentuk_legalitas':
            case 'sertifikasi_produk':
                $this->simpanProfil($umkm, $kolom, $nilai);
                $this->simpanLegalitas($umkm, $kolom, $nilai);
                break;

            case 'metode_pencatatan':
                $this->simpanProfil($umkm, $kolom, $nilai);
                $this->keuangan($umkm)->update(['memiliki_pencatatan' => self::punyaPencatatan($nilai)]);
                break;
            case 'omzet_bulanan':
                $this->keuangan($umkm)->update(['omzet_tahunan' => $nilai !== null ? (float) $nilai * 12 : null]);
                break;

            default: // program_bi, produk_lainnya, kapasitas_produk_lainnya, pembiayaan_*, rekomendasi_program
                $this->simpanProfil($umkm, $kolom, $nilai);
        }
    }

    // ==================== Penurunan data (dipakai juga oleh import) ====================

    public static function bersihkanTelepon(?string $telepon): string
    {
        $angka = preg_replace('/[^0-9]/', '', (string) $telepon);
        return substr(preg_replace('/^0/', '62', $angka), 0, 20);
    }

    /** Platform online yang disebut di jawaban "saluran pemasaran". */
    public static function platformDari(?string $teks): array
    {
        $teks = strtolower((string) $teks);
        return array_values(array_filter(
            ['tokopedia', 'shopee', 'tiktok', 'instagram', 'facebook', 'whatsapp'],
            fn ($p) => str_contains($teks, $p)
        ));
    }

    /** URL marketplace tertentu dari teks bebas (null bila tidak ada). */
    public static function marketplaceDari(?string $teks, string $platform): ?string
    {
        preg_match_all('#https?://[^\s"\'<>,;]+#i', (string) $teks, $m);
        foreach ($m[0] as $url) {
            if (str_contains(strtolower((string) parse_url($url, PHP_URL_HOST)), $platform)) {
                return substr($url, 0, 255);
            }
        }
        return null;
    }

    /** Kolom legalitas yang disebut di teks: [kolom => bool]. */
    public static function legalitasDari(?string $bentuk, ?string $sertifikasi): array
    {
        $cek = fn (?string $t, array $kunci) => (bool) preg_match(
            '/\b(' . implode('|', $kunci) . ')\b/i',
            str_replace('-', '', (string) $t)
        );

        return [
            'nomor_nib'   => $cek($bentuk, ['nib']),
            'nomor_siup'  => $cek($bentuk, ['siup']),
            'nomor_npwp'  => $cek($bentuk, ['npwp']),
            'nomor_akte'  => $cek($bentuk, ['akta', 'akte']),
            'nomor_halal' => $cek($sertifikasi, ['halal']),
            'nomor_bpom'  => $cek($sertifikasi, ['bpom']),
            'nomor_pirt'  => $cek($sertifikasi, ['pirt', 'spp ?irt']),
            'nomor_sni'   => $cek($sertifikasi, ['sni']),
        ];
    }

    public static function punyaPencatatan(?string $metode): bool
    {
        return (bool) preg_match('/digital|aplikasi/i', (string) $metode);
    }

    // ==================== Internal ====================

    private function simpanProfil(Umkm $umkm, string $kolom, string|array|null $nilai): void
    {
        $umkm->profil()->updateOrCreate([], [$kolom => $nilai]);
    }

    private function simpanProdukUnggulan(Umkm $umkm, ?Produk $produk, string $nama): void
    {
        if ($produk) {
            $produk->update(['nama_produk' => $nama]);
            return;
        }

        $umkm->produk()->create([
            'nama_produk' => $nama,
            'urutan'      => (int) $umkm->produk()->max('urutan') + 1,
            'is_active'   => true,
        ])->jadikanUtama();
    }

    /**
     * Sinkronkan kolom nomor_* legalitas dengan jawaban teks. Nomor asli yang sudah ada
     * dipertahankan; yang tidak lagi disebut dikosongkan.
     */
    private function simpanLegalitas(Umkm $umkm, string $kolom, ?string $nilai): void
    {
        $legalitas = $umkm->legalitas()->firstOrCreate([]);
        $bagian    = $kolom === 'bentuk_legalitas'
            ? ['nomor_nib', 'nomor_siup', 'nomor_npwp', 'nomor_akte']
            : ['nomor_halal', 'nomor_bpom', 'nomor_pirt', 'nomor_sni'];

        $ada = $kolom === 'bentuk_legalitas'
            ? self::legalitasDari($nilai, null)
            : self::legalitasDari(null, $nilai);

        $data = [];
        foreach ($bagian as $k) {
            $data[$k] = $ada[$k] ? ($legalitas->{$k} ?: 'ADA') : null;
        }

        $legalitas->update($data);
    }

    private function keuangan(Umkm $umkm): Keuangan
    {
        // Simpan ke relasi agar beberapa kolom keuangan dalam satu proses tidak membuat baris ganda
        if (! $umkm->keuanganTerakhir) {
            $umkm->setRelation('keuanganTerakhir', $umkm->keuangan()->create(['tahun' => date('Y')]));
        }
        return $umkm->keuanganTerakhir;
    }

    private function daftarAda($legalitas, array $peta): ?string
    {
        if (! $legalitas) return null;
        $ada = array_values(array_filter($peta, fn ($label, $kolom) => filled($legalitas->{$kolom}), ARRAY_FILTER_USE_BOTH));
        return $ada ? implode(', ', $ada) : null;
    }
}
