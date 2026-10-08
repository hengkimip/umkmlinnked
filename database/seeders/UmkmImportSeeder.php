<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\PemilikUsaha;
use App\Models\Umkm;
use App\Models\Legalitas;
use App\Models\Keuangan;
use App\Models\Pemasaran;
use App\Models\Pembiayaan;
use App\Models\Produk;
use App\Services\UmkmScoringService;

class UmkmImportSeeder extends Seeder
{
    public function run(): void
    {
        $csvPath = database_path('seeders/data/umkm_data.csv');

        if (!file_exists($csvPath)) {
            $this->command->error("File tidak ditemukan: {$csvPath}");
            return;
        }

        $handle = fopen($csvPath, 'r');
        $header = fgetcsv($handle); // Baca baris header, skip
        $row    = 0;
        $errors = [];

        DB::beginTransaction();

        try {
            while (($data = fgetcsv($handle)) !== false) {
                $row++;

                // Skip baris kosong
                if (empty(array_filter($data))) continue;

                // Map kolom CSV ke variabel
                [
                    $id, $opd_id, $nama_pemilik, $alamat_pemilik,
                    $whatsapp, $email, $program_bi,
                    $nama_usaha, $alamat_usaha, $tahun_berdiri,
                    $sektor, $jumlah_karyawan, $kapasitas_produksi,
                    $produk_lain, $kapasitas_produk_lain,
                    $saluran_pemasaran, $jangkauan_pasar,
                    $wa_bisnis, $instagram, $marketplace, $website,
                    $legalitas, $sertifikasi, $pencatatan_keuangan,
                    $sudah_pembiayaan, $nama_lembaga_pembiayaan,
                    $rencana_pembiayaan, $produk_unggulan, $omzet_perbulan
                ] = array_pad($data, 29, null);

                // --- 1. BUAT / CARI PEMILIK USAHA ---
                $pemilik = PemilikUsaha::firstOrCreate(
                    ['telepon' => $this->cleanPhone($whatsapp)],
                    [
                        'nama_lengkap' => trim($nama_pemilik),
                        'nik'          => $this->generateNik($row), // placeholder
                        'jenis_kelamin'=> 'L', // default, bisa diupdate manual
                        'alamat'       => trim($alamat_pemilik ?? ''),
                        'kabupaten'    => 'Pontianak', // default
                        'kecamatan'    => '-',
                        'telepon'      => $this->cleanPhone($whatsapp),
                        'email'        => trim($email ?? ''),
                    ]
                );

                // --- 2. BUAT UMKM ---
                $sektorBersih = $this->normalizeSektor(trim($sektor ?? ''));
                $slug = Str::slug(trim($nama_usaha)) . '-' . Str::random(5);

                // Hindari duplikat nama usaha
                $existingUmkm = Umkm::where('nama_usaha', trim($nama_usaha))->first();
                if ($existingUmkm) {
                    $this->command->warn("Baris {$row}: UMKM '{$nama_usaha}' sudah ada, dilewati.");
                    continue;
                }

                $umkm = Umkm::create([
                    'pemilik_usaha_id'   => $pemilik->id,
                    'opd_id'             => $opd_id ?: null,
                    'nama_usaha'         => trim($nama_usaha),
                    'slug'               => $slug,
                    'deskripsi'          => trim($program_bi ?? ''),
                    'sektor'             => $sektorBersih,
                    'kabupaten'          => 'Pontianak', // default, sesuaikan
                    'kecamatan'          => '-',
                    'alamat_usaha'       => trim($alamat_usaha ?? ''),
                    'whatsapp'           => $this->cleanPhone($wa_bisnis ?: $whatsapp),
                    'email'              => trim($email ?? ''),
                    'instagram'          => trim($instagram ?? ''),
                    'website'            => trim($website ?? ''),
                    'jumlah_tenaga_kerja'=> (int) ($jumlah_karyawan ?? 0),
                    'tahun_berdiri'      => $this->cleanYear($tahun_berdiri),
                    'status'             => 'aktif',
                    'klasifikasi'        => 'dasar',
                ]);

                // Marketplace (simpan ke shopee/tokopedia jika ada)
                if (!empty($marketplace)) {
                    if (str_contains(strtolower($marketplace), 'shopee')) {
                        $umkm->update(['shopee' => trim($marketplace)]);
                    } elseif (str_contains(strtolower($marketplace), 'tokopedia')) {
                        $umkm->update(['tokopedia' => trim($marketplace)]);
                    }
                }

                // --- 3. LEGALITAS & SERTIFIKASI ---
                $legalitasData = $this->parseLegalitas($legalitas ?? '');
                $sertifData    = $this->parseSertifikasi($sertifikasi ?? '');

                Legalitas::create(array_merge(
                    ['umkm_id' => $umkm->id],
                    $legalitasData,
                    $sertifData
                ));

                // --- 4. PEMASARAN ---
                $platformList = $this->parsePlatform($saluran_pemasaran ?? '');
                $jangkauan    = $this->normalizeJangkauan($jangkauan_pasar ?? '');

                Pemasaran::create([
                    'umkm_id'          => $umkm->id,
                    'platform_online'  => json_encode($platformList),
                    'jangkauan_pasar'  => $jangkauan,
                    'strategi_pemasaran' => trim($saluran_pemasaran ?? ''),
                ]);

                // --- 5. KEUANGAN ---
                $omzetBersih = $this->cleanAngka($omzet_perbulan ?? '0');
                Keuangan::create([
                    'umkm_id'             => $umkm->id,
                    'tahun'               => 2026,
                    'omzet_tahunan'       => $omzetBersih * 12,
                    'memiliki_pencatatan' => !empty($pencatatan_keuangan) && strtolower($pencatatan_keuangan) !== 'tidak',
                    'jenis_pencatatan'    => $this->normalizePencatatan($pencatatan_keuangan ?? ''),
                ]);

                // --- 6. PEMBIAYAAN ---
                if (
                    strtolower(trim($sudah_pembiayaan ?? '')) === 'ya'
                    && !empty($nama_lembaga_pembiayaan)
                ) {
                    [$namaLembaga, $plafond] = $this->parsePembiayaan($nama_lembaga_pembiayaan);
                    Pembiayaan::create([
                        'umkm_id'          => $umkm->id,
                        'nama_bank_lembaga'=> $namaLembaga,
                        'jenis_pembiayaan' => 'KUR',
                        'jumlah_pinjaman'  => $plafond,
                        'tanggal_mulai'    => now(),
                        'status'           => 'aktif',
                    ]);
                }

                // --- 7. PRODUK UNGGULAN ---
                if (!empty($produk_unggulan)) {
                    Produk::create([
                        'umkm_id'            => $umkm->id,
                        'nama_produk'        => trim($produk_unggulan),
                        'kapasitas_produksi' => (int) $this->cleanAngka($kapasitas_produksi ?? '0'),
                        'satuan_kapasitas'   => 'bulan',
                        'is_unggulan'        => true,
                        'is_active'          => true,
                    ]);
                }

                // --- 8. PRODUK LAIN (jika ada) ---
                if (!empty($produk_lain) && strtolower($produk_lain) !== 'tidak') {
                    Produk::create([
                        'umkm_id'            => $umkm->id,
                        'nama_produk'        => trim($produk_lain),
                        'kapasitas_produksi' => (int) $this->cleanAngka($kapasitas_produk_lain ?? '0'),
                        'satuan_kapasitas'   => 'bulan',
                        'is_unggulan'        => false,
                        'is_active'          => true,
                    ]);
                }

                $this->command->info("Baris {$row}: UMKM '{$nama_usaha}' berhasil diimpor.");
            }

            fclose($handle);

            // --- 9. HITUNG SKOR SEMUA UMKM ---
            $this->command->info("Menghitung skor klasifikasi...");
            $scoring = new UmkmScoringService();
            Umkm::with(['legalitas','produk','pemasaran','keuanganTerakhir'])->each(function ($umkm) use ($scoring) {
                $scoring->simpan($umkm);
            });

            DB::commit();
            $this->command->info("Import selesai! Total {$row} baris diproses.");

        } catch (\Exception $e) {
            DB::rollBack();
            fclose($handle);
            $this->command->error("Import gagal di baris {$row}: " . $e->getMessage());
            throw $e;
        }
    }

    // ===== HELPER METHODS =====

    private function cleanPhone(?string $phone): string
    {
        if (empty($phone)) return '0';
        $phone = preg_replace('/[^0-9]/', '', $phone);
        if (str_starts_with($phone, '62')) {
            $phone = '0' . substr($phone, 2);
        }
        if (str_starts_with($phone, '8')) {
            $phone = '0' . $phone;
        }
        return $phone ?: '0';
    }

    private function cleanYear(?string $year): ?int
    {
        if (empty($year)) return null;
        $y = (int) preg_replace('/[^0-9]/', '', $year);
        return ($y >= 1900 && $y <= date('Y')) ? $y : null;
    }

    private function cleanAngka(?string $angka): int
    {
        if (empty($angka)) return 0;
        // Hapus "Rp", titik ribuan, koma
        $angka = preg_replace('/[^0-9]/', '', $angka);
        return (int) $angka;
    }

    private function generateNik(int $row): string
    {
        // NIK placeholder unik — harus diupdate manual dengan NIK asli
        return '6171' . str_pad($row, 12, '0', STR_PAD_LEFT);
    }

    private function normalizeSektor(?string $sektor): string
    {
        $sektor = strtolower(trim($sektor ?? ''));
        return match(true) {
            str_contains($sektor, 'kuliner')
                || str_contains($sektor, 'makanan')
                || str_contains($sektor, 'minuman')    => 'kuliner',
            str_contains($sektor, 'fashion')
                || str_contains($sektor, 'fesyen')
                || str_contains($sektor, 'wastra')
                || str_contains($sektor, 'pakaian')
                || str_contains($sektor, 'tekstil')    => 'fashion',
            str_contains($sektor, 'kerajinan')
                || str_contains($sektor, 'handicraft') => 'kerajinan',
            str_contains($sektor, 'pertanian')
                || str_contains($sektor, 'agro')
                || str_contains($sektor, 'perikanan')
                || str_contains($sektor, 'ikan')       => 'pertanian',
            str_contains($sektor, 'jasa')
                || str_contains($sektor, 'teknologi')
                || str_contains($sektor, 'digital')    => 'jasa',
            default                                     => 'lainnya',
        };
    }

    private function normalizeJangkauan(?string $jangkauan): string
    {
        $j = strtolower(trim($jangkauan ?? ''));
        return match(true) {
            str_contains($j, 'ekspor')
                || str_contains($j, 'internasional') => 'ekspor',
            str_contains($j, 'nasional')
                || str_contains($j, 'indonesia')     => 'nasional',
            str_contains($j, 'regional')
                || str_contains($j, 'provinsi')
                || str_contains($j, 'kalimantan')    => 'regional',
            default                                   => 'lokal',
        };
    }

    private function parseLegalitas(?string $text): array
    {
        $text = strtolower($text ?? '');
        return [
            'nomor_nib'  => str_contains($text, 'nib')  ? 'ADA' : null,
            'nomor_siup' => str_contains($text, 'siup') ? 'ADA' : null,
            'nomor_npwp' => str_contains($text, 'npwp') ? 'ADA' : null,
            'nomor_akte' => str_contains($text, 'akte')
                         || str_contains($text, 'akta') ? 'ADA' : null,
        ];
    }

    private function parseSertifikasi(?string $text): array
    {
        $text = strtolower($text ?? '');
        return [
            'nomor_halal' => str_contains($text, 'halal') ? 'ADA' : null,
            'nomor_bpom'  => str_contains($text, 'bpom')  ? 'ADA' : null,
            'nomor_pirt'  => str_contains($text, 'pirt')  ? 'ADA' : null,
            'nomor_sni'   => str_contains($text, 'sni')   ? 'ADA' : null,
        ];
    }

    private function parsePlatform(?string $text): array
    {
        $text  = strtolower($text ?? '');
        $hasil = [];
        if (str_contains($text, 'shopee'))    $hasil[] = 'shopee';
        if (str_contains($text, 'tokopedia')) $hasil[] = 'tokopedia';
        if (str_contains($text, 'tiktok'))    $hasil[] = 'tiktok';
        if (str_contains($text, 'instagram')) $hasil[] = 'instagram';
        if (str_contains($text, 'facebook'))  $hasil[] = 'facebook';
        if (str_contains($text, 'whatsapp')
            || str_contains($text, 'wa'))     $hasil[] = 'whatsapp';
        return $hasil;
    }

    private function parsePembiayaan(?string $text): array
    {
        // Format: "BRI - 50000000" atau "Bank Mandiri 100.000.000"
        $text   = $text ?? '';
        $angka  = $this->cleanAngka(preg_replace('/[^0-9.,]/', '', $text));
        $nama   = trim(preg_replace('/[\d.,\-]+/', '', $text));
        return [trim($nama) ?: 'Tidak disebutkan', $angka];
    }

    private function normalizePencatatan(?string $text): ?string
    {
        $text = strtolower(trim($text ?? ''));
        if (str_contains($text, 'aplikasi')
            || str_contains($text, 'digital')) return 'aplikasi';
        if (str_contains($text, 'akuntan')
            || str_contains($text, 'pembukuan')) return 'akuntan';
        if (!empty($text) && $text !== 'tidak') return 'manual';
        return null;
    }
}