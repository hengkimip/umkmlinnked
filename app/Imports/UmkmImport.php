<?php
namespace App\Imports;

use App\Models\Umkm;
use App\Models\PemilikUsaha;
use App\Models\Legalitas;
use App\Models\Pemasaran;
use App\Models\Keuangan;
use App\Models\Produk;
use App\Services\UmkmScoringService;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Maatwebsite\Excel\Concerns\WithChunkReading;

/**
 * @method \Illuminate\Support\Collection failures()
 */
class UmkmImport implements
    ToCollection,
    WithHeadingRow,
    SkipsOnError,
    WithChunkReading
{
    use SkipsErrors;

    // ... sisa kode tidak berubah

    private int $opdId;
    private UmkmScoringService $scoring;

    public int $imported = 0;

    public function __construct(int $opdId)
    {
        $this->opdId   = $opdId;
        $this->scoring = new UmkmScoringService();
    }

    public function chunkSize(): int { return 100; }

    public function collection(Collection $rows)
    {
        // DEBUG: cek kolom yang terbaca dari CSV
    if ($rows->first()) {
        $kolomCSV = array_keys($rows->first()->toArray());
         \Illuminate\Support\Facades\Log::info('Kolom CSV yang terbaca: ' . implode(', ', $kolomCSV));
        echo "Kolom CSV: " . implode(', ', $kolomCSV) . "\n";
    }
        foreach ($rows as $index => $row) {
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
                    $legalitasRaw  = strtolower($row['bentuk_legalitas_usaha_yang_dimiliki'] ?? '');
                    $sertifRaw     = strtolower($row['sertifikasi_produk_yang_dimiliki'] ?? '');

                    Legalitas::create([
                        'umkm_id'     => $umkm->id,
                        'nomor_nib'   => str_contains($legalitasRaw, 'nib')  ? 'ADA' : null,
                        'nomor_siup'  => str_contains($legalitasRaw, 'siup') ? 'ADA' : null,
                        'nomor_npwp'  => str_contains($legalitasRaw, 'npwp') ? 'ADA' : null,
                        'nomor_halal' => str_contains($sertifRaw, 'halal')   ? 'ADA' : null,
                        'nomor_bpom'  => str_contains($sertifRaw, 'bpom')    ? 'ADA' : null,
                        'nomor_pirt'  => str_contains($sertifRaw, 'pirt')    ? 'ADA' : null,
                    ]);

                    // 5. Pemasaran
                    $saluranRaw   = strtolower($row['saluran_pemasaran_produk_selama_ini'] ?? '');
                    $jangkauanRaw = strtolower($row['jangkauan_pasar_utama_saat_ini'] ?? '');
                    $platforms    = [];
                    foreach (['tokopedia','shopee','tiktok','instagram','facebook','whatsapp'] as $p) {
                        if (str_contains($saluranRaw, $p)) $platforms[] = $p;
                    }

                    Pemasaran::create([
                        'umkm_id'          => $umkm->id,
                        'platform_online'  => $platforms,
                        'jangkauan_pasar'  => $this->mapJangkauan($jangkauanRaw),
                        'memiliki_website' => !empty($row['url_link_website']),
                    ]);

                    // 6. Keuangan
                    $omzetRaw = $this->cleanAngka($row['berapa_rata_rata_omzet_usaha_anda_perbulan'] ?? '0');

                    Keuangan::create([
                        'umkm_id'             => $umkm->id,
                        'tahun'               => date('Y'),
                        'omzet_tahunan'       => $omzetRaw * 12,
                        'memiliki_pencatatan' => str_contains(
                            strtolower($row['bagaimana_metode_pencatatan_keuangan_usaha_anda_saat_ini'] ?? ''),
                            'digital'
                        ) || str_contains(
                            strtolower($row['bagaimana_metode_pencatatan_keuangan_usaha_anda_saat_ini'] ?? ''),
                            'aplikasi'
                        ),
                    ]);

                    // 7. Produk unggulan
                    $produkUnggulan = $row['produk_jasa_unggulan'] ?? null;
                    if ($produkUnggulan) {
                        Produk::create([
                            'umkm_id'            => $umkm->id,
                            'nama_produk'        => $produkUnggulan,
                            'kapasitas_produksi' => $this->cleanAngka($row['kapasitas_produksi_per_bulan_pcskg'] ?? '0'),
                            'satuan_kapasitas'   => 'bulan',
                            'is_unggulan'        => true,
                            'is_active'          => true,
                        ]);
                    }

                    // 8. Hitung skor otomatis
                    $this->scoring->simpan($umkm);
                });

                $this->imported++;

            } catch (\Throwable $e) {
                $this->errors[] = "Baris " . ($index + 2) . ": " . $e->getMessage();
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
        $kalbar = [
            'Pontianak','Singkawang','Sambas','Bengkayang','Landak',
            'Mempawah','Sanggau','Sekadau','Sintang','Melawi',
            'Kapuas Hulu','Ketapang','Kayong Utara','Kubu Raya',
        ];
        foreach ($kalbar as $kab) {
            if (stripos($alamat, $kab) !== false) return $kab;
        }
        return 'Tidak Diketahui';
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