<?php
namespace App\Imports;

use App\Models\Umkm;
use App\Models\PemilikUsaha;
use App\Models\Legalitas;
use App\Models\Pemasaran;
use App\Models\Keuangan;
use App\Models\Produk;
use App\Models\ProfilUmkm;
use App\Services\ProfilUmkmService;
use App\Services\UmkmScoringService;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Maatwebsite\Excel\Concerns\WithChunkReading;

class UmkmImport implements
    ToCollection,
    WithHeadingRow,
    SkipsOnError,
    WithChunkReading
{
    use SkipsErrors;

    private int $opdId;
    private UmkmScoringService $scoring;

    public int $imported = 0;

    // Nomor baris di file (baris 1 = header), berlanjut antar-chunk
    private int $rowNumber = 1;

    public function __construct(int $opdId)
    {
        $this->opdId   = $opdId;
        $this->scoring = new UmkmScoringService();
    }

    public function chunkSize(): int { return 100; }

    public function collection(Collection $rows)
    {
        if ($rows->first()) {
            $kolomCSV = array_keys($rows->first()->toArray());
            \Illuminate\Support\Facades\Log::info('Kolom CSV yang terbaca: ' . implode(', ', $kolomCSV));
        }

        foreach ($rows as $row) {
            $this->rowNumber++;

            try {
                DB::transaction(function () use ($row) {
                    // 1. Simpan atau update Pemilik Usaha
                    $wa = $this->cleanPhone($row['no_whatsapp'] ?? '');

                    $pemilik = PemilikUsaha::firstOrCreate(
                        ['telepon' => $wa ?: ('row-' . Str::random(8))],
                        [
                            'nama_lengkap'  => $row['nama_pemilik_usaha'] ?? 'Tidak Diketahui',
                            'nik'           => 'NIK-' . Str::random(10),
                            'jenis_kelamin' => 'L',
                            'alamat'        => $row['alamat_lengkap'] ?? '-',
                            'kabupaten'     => $this->extractKabupaten($row['alamat_lengkap'] ?? ''),
                            'kecamatan'     => '-',
                            'telepon'       => $wa ?: '-',
                            'email'         => $row['alamat_e_mail'] ?? null,
                        ]
                    );

                    // 2. Buat slug unik
                    $namaUsaha = $row['nama_umkmusaha'] ?? ('UMKM-' . Str::random(6));
                    $slug      = Str::slug($namaUsaha) . '-' . Str::random(5);

                    // 3. Simpan UMKM
                    $umkm = Umkm::create([
                        'pemilik_usaha_id'    => $pemilik->id,
                        'opd_id'              => $this->opdId,
                        'nama_usaha'          => $namaUsaha,
                        'slug'                => $slug,
                        'sektor'              => $this->mapSektor($row['sektor_usaha'] ?? ''),
                        'kabupaten'           => $this->extractKabupaten($row['alamat_usaha'] ?? ''),
                        'kecamatan'           => '-',
                        'alamat_usaha'        => $row['alamat_usaha'] ?? '-',
                        'whatsapp'            => $this->cleanPhone($row['nomor_link_wa_bisnis'] ?? $wa),
                        'instagram'           => $row['urllink_instagram_usaha_facebook'] ?? null,
                        'tokopedia'           => $this->extractMarketplace($row['urllink_marketplace_usaha_yang_dimiliki'] ?? '', 'tokopedia'),
                        'shopee'              => $this->extractMarketplace($row['urllink_marketplace_usaha_yang_dimiliki'] ?? '', 'shopee'),
                        'website'             => $row['url_link_website'] ?? null,
                        'jumlah_tenaga_kerja' => (int) ($row['jumlah_karyawan'] ?? 0),
                        'tahun_berdiri'       => $this->cleanYear($row['tahun_berdirinya_usaha'] ?? null),
                        'status'              => 'aktif',
                    ]);

                    // 4. Legalitas
                    $legalitasRaw = $this->teks($row, 'bentuk_legalitas_usaha_yang_dimiliki');
                    $sertifRaw    = $this->teks($row, 'sertifikasi_produk_yang_dimiliki');
                    $adaLegal     = ProfilUmkmService::legalitasDari($legalitasRaw, $sertifRaw);

                    Legalitas::create(['umkm_id' => $umkm->id] + array_map(fn ($ada) => $ada ? 'ADA' : null, $adaLegal));

                    // 5. Pemasaran
                    $saluranRaw   = $this->teks($row, 'saluran_pemasaran_produk_selama_ini');
                    $jangkauanRaw = strtolower($row['jangkauan_pasar_utama_saat_ini'] ?? '');

                    Pemasaran::create([
                        'umkm_id'          => $umkm->id,
                        'platform_online'  => ProfilUmkmService::platformDari($saluranRaw),
                        'jangkauan_pasar'  => $this->mapJangkauan($jangkauanRaw),
                        'memiliki_website' => !empty($row['url_link_website']),
                    ]);

                    // 6. Keuangan
                    $omzetRaw     = $this->cleanAngka($row['berapa_rata_rata_omzet_usaha_anda_perbulan'] ?? '0');
                    $pencatatan   = $this->teks($row, 'bagaimana_metode_pencatatan_keuangan_usaha_anda_saat_ini');

                    Keuangan::create([
                        'umkm_id'             => $umkm->id,
                        'tahun'               => date('Y'),
                        'omzet_tahunan'       => $omzetRaw * 12,
                        'memiliki_pencatatan' => ProfilUmkmService::punyaPencatatan($pencatatan),
                    ]);

                    // 7. Jawaban kuesioner apa adanya (ditampilkan & diubah di "Kelola Profil UMKM").
                    //    Judul kolom pembiayaan: versi template (pendek) atau versi formulir (panjang).
                    ProfilUmkm::create([
                        'umkm_id'                  => $umkm->id,
                        'program_bi'               => $this->teks($row, 'program_yang_pernah_diikuti_dari_bank_indonesia'),
                        'produk_lainnya'           => $this->teks($row, 'apakah_memiliki_jenis_produk_lainnya_mohon_disebutkan_secara_spesifik'),
                        'kapasitas_produk_lainnya' => $this->teks($row, 'berapa_kapasitas_produksi_produk_tersebut'),
                        'saluran_pemasaran'        => $saluranRaw,
                        'marketplace'              => $this->teks($row, 'urllink_marketplace_usaha_yang_dimiliki'),
                        'bentuk_legalitas'         => $legalitasRaw,
                        'sertifikasi_produk'       => $sertifRaw,
                        'metode_pencatatan'        => $pencatatan,
                        'pembiayaan_2026'          => mb_substr((string) $this->teks($row,
                            'apakah_pada_tahun_2026_sudah_mendapatkan_pembiayaan',
                            'apakah_pada_tahun_2026_sudah_mendapatkan_pembiayaan_dari_lembaga_keuangan_perbankan_dan_atau_non_perbankan'), 0, 255) ?: null,
                        'pembiayaan_diterima'      => $this->teks($row,
                            'jika_sudah_sebutkan_nama_lembaga_dan_jumlah_plafond',
                            'jika_sudah_mendapatkan_akses_pembiayaan_sebutkan_nama_lembaga_keuangan_dan_jumlah_plafond_yang_diterima'),
                        'rencana_pembiayaan'       => $this->teks($row,
                            'jika_ada_rencana_sebutkan_nama_lembaga_dan_jumlah_plafond',
                            'jika_ada_rencana_akses_pembiayaan_sebutkan_nama_lembaga_keuangan_dan_jumlah_plafond_yang_akan_diajukan'),
                    ]);

                    // 8. Produk unggulan
                    $produkUnggulan = $row['produk_jasa_unggulan'] ?? null;
                    if ($produkUnggulan) {
                        $fotoUrl = $this->teks($row, 'foto_url', 'foto_produk');

                        Produk::create([
                            'umkm_id'            => $umkm->id,
                            'nama_produk'        => $produkUnggulan,
                            'kapasitas_produksi' => $this->cleanAngka($row['kapasitas_produksi_per_bulan_pcskg'] ?? '0'),
                            'satuan_kapasitas'   => 'bulan',
                            'foto_url'           => $fotoUrl && preg_match('#^https?://#i', $fotoUrl) ? $fotoUrl : null,
                            'is_unggulan'        => true,
                            'badge'              => 'unggulan',
                            'is_active'          => true,
                        ]);
                    }

                    // 9. Hitung skor otomatis
                    $this->scoring->simpan($umkm);
                });

                $this->imported++;

            } catch (\Throwable $e) {
                // Detail teknis hanya ke log; pengguna menerima pesan ringkas
                \Illuminate\Support\Facades\Log::warning("Import UMKM baris {$this->rowNumber} gagal: " . $e->getMessage());
                $nama = trim((string) ($row['nama_umkmusaha'] ?? ''));
                $this->errors[] = "Baris {$this->rowNumber}" . ($nama !== '' ? " ({$nama})" : '')
                    . ': data tidak valid atau kolom wajib kosong.';
            }
        }
    }



    private function downloadFotoDariDrive(): void
{
    $produkDenganUrl = \App\Models\Produk::whereNotNull('foto_url')
        ->whereNull('foto')
        ->get();

    foreach ($produkDenganUrl as $produk) {
        try {
            $url = $this->konversiUrlDownload($produk->foto_url);

            $response = \Illuminate\Support\Facades\Http::timeout(20)->get($url);

            if (!$response->successful()) continue;

            // Deteksi ekstensi
            $contentType = $response->header('Content-Type');
            $ext = match(true) {
                str_contains($contentType, 'png')  => 'png',
                str_contains($contentType, 'webp') => 'webp',
                default                             => 'jpg',
            };

            $namaFile = 'produk/foto/' .
                        \Illuminate\Support\Str::slug($produk->nama_produk) .
                        '-' . $produk->id . '.' . $ext;

            \Illuminate\Support\Facades\Storage::disk('public')
                ->put($namaFile, $response->body());

            $produk->update(['foto' => $namaFile]);

            // Jeda kecil agar tidak kena rate limit Google
            usleep(300000); // 0.3 detik

        } catch (\Throwable $e) {
            // Gagal download tidak menghentikan proses
            \Illuminate\Support\Facades\Log::warning("Gagal download foto produk ID {$produk->id}: " . $e->getMessage());
        }
    }
}

private function konversiUrlDownload(string $url): string
{
    if (preg_match('/drive\.google\.com\/file\/d\/([a-zA-Z0-9_-]+)/', $url, $m)) {
        return "https://drive.google.com/uc?export=download&id={$m[1]}";
    }
    if (preg_match('/[?&]id=([a-zA-Z0-9_-]+)/', $url, $m)) {
        return "https://drive.google.com/uc?export=download&id={$m[1]}";
    }
    return $url;
}
    // ===== HELPER METHODS =====

    /**
     * Teks jawaban dari kolom pertama yang terisi (null bila kosong / "-").
     */
    private function teks($row, string ...$kunci): ?string
    {
        foreach ($kunci as $k) {
            $v = trim((string) ($row[$k] ?? ''));
            if ($v !== '' && $v !== '-') return $v;
        }
        return null;
    }

    private function cleanPhone(?string $phone): string
    {
        if (!$phone) return '';
        $phone = preg_replace('/[^0-9]/', '', $phone);
        return preg_replace('/^0/', '62', $phone);
    }

    private function cleanYear($year): ?int
    {
        $y = (int) preg_replace('/[^0-9]/', '', (string) $year);
        return ($y >= 1900 && $y <= date('Y')) ? $y : null;
    }

    private function cleanAngka(?string $val): float
    {
        if (!$val) return 0;
        // Hapus Rp, titik ribuan, spasi
        $val = preg_replace('/[^0-9,.]/', '', $val);
        $val = str_replace(',', '.', $val);
        return (float) $val;
    }

    private function extractKabupaten(string $alamat): string
    {
        return Umkm::tebakKabupaten($alamat) ?? Umkm::KABUPATEN_KOSONG;
    }

    private function mapSektor(string $sektor): string
    {
        $sektor = strtolower($sektor);
        $map = [
            'kuliner'     => ['makanan','minuman','kuliner','pangan','kue','bumbu','madu'],
            'fashion'     => ['fashion','pakaian','baju','busana','tekstil'],
            'kerajinan'   => ['kerajinan','craft','anyaman','tenun'],
            'pertanian'   => ['pertanian','perkebunan','tani','kebun'],
            'perikanan'   => ['perikanan','ikan','nelayan','kelautan'],
            'jasa'        => ['jasa','layanan','service'],
            'teknologi'   => ['teknologi','digital','it','aplikasi'],
            'perdagangan' => ['dagang','toko','retail','distributor'],
        ];
        foreach ($map as $key => $keywords) {
            foreach ($keywords as $kw) {
                if (str_contains($sektor, $kw)) return $key;
            }
        }
        return 'lainnya';
    }

    private function mapJangkauan(string $jangkauan): string
    {
        if (str_contains($jangkauan, 'ekspor') || str_contains($jangkauan, 'internasional')) return 'ekspor';
        if (str_contains($jangkauan, 'nasional') || str_contains($jangkauan, 'indonesia')) return 'nasional';
        if (str_contains($jangkauan, 'regional') || str_contains($jangkauan, 'provinsi')) return 'regional';
        return 'lokal';
    }

    private function extractMarketplace(string $url, string $platform): ?string
    {
        if (str_contains(strtolower($url), $platform)) return $url;
        return null;
    }
}