<?php
namespace App\Console\Commands;

use App\Models\Umkm;
use App\Models\Produk;
use Illuminate\Console\Command;

class UpdateFotoDariCsv extends Command
{
    protected $signature   = 'umkm:update-foto {file : Nama file CSV di storage/app}';
    protected $description = 'Update kolom foto_url pada produk yang sudah ada berdasarkan posisi kolom CSV';

    // Index kolom sesuai template (0-based)
    const IDX_NAMA_USAHA  = 7;   // "Nama UMKM/Usaha"
    const IDX_NAMA_PRODUK = 27;  // "Produk / Jasa unggulan"
    const IDX_FOTO_URL    = 29;  // "foto_produk" (kolom tambahan di akhir)

    public function handle(): void
    {
        $fileName = $this->argument('file');
        $path     = storage_path('app/' . $fileName);

        if (!file_exists($path)) {
            $this->error("File tidak ditemukan: {$path}");
            return;
        }

        $handle = fopen($path, 'r');
        $header = fgetcsv($handle); // lewati baris header

        $this->info("Jumlah kolom terdeteksi: " . count($header));
        $this->info("Kolom nama usaha (index " . self::IDX_NAMA_USAHA . "): " . ($header[self::IDX_NAMA_USAHA] ?? '?'));
        $this->info("Kolom nama produk (index " . self::IDX_NAMA_PRODUK . "): " . ($header[self::IDX_NAMA_PRODUK] ?? '?'));
        $this->info("Kolom foto (index " . self::IDX_FOTO_URL . "): " . ($header[self::IDX_FOTO_URL] ?? '?'));
        $this->newLine();

        if (!$this->confirm('Kolom di atas sudah benar? Lanjutkan?', true)) {
            fclose($handle);
            return;
        }

        $diupdate    = 0;
        $dilewati    = 0;
        $tidakketemu = 0;
        $baris       = 1;

        while (($row = fgetcsv($handle)) !== false) {
            $baris++;

            $namaUsaha = trim($row[self::IDX_NAMA_USAHA] ?? '');
            $fotoUrl   = trim($row[self::IDX_FOTO_URL] ?? '');

            if (empty($namaUsaha) || empty($fotoUrl)) {
                $dilewati++;
                continue;
            }

            if (!filter_var($fotoUrl, FILTER_VALIDATE_URL)) {
                $this->warn("Baris {$baris}: URL tidak valid untuk '{$namaUsaha}', dilewati.");
                $dilewati++;
                continue;
            }

            // Cari UMKM berdasarkan nama (case-insensitive, partial match)
            $umkm = Umkm::whereRaw('LOWER(nama_usaha) LIKE ?', ['%' . strtolower($namaUsaha) . '%'])
                         ->first();

            if (!$umkm) {
                $this->warn("Baris {$baris}: UMKM '{$namaUsaha}' TIDAK DITEMUKAN di database.");
                $tidakketemu++;
                continue;
            }

            $produk = Produk::where('umkm_id', $umkm->id)
                             ->orderBy('urutan')
                             ->first();

            if (!$produk) {
                $this->warn("Baris {$baris}: Produk untuk '{$umkm->nama_usaha}' tidak ditemukan.");
                $tidakketemu++;
                continue;
            }

            // Hanya update jika kolom foto masih kosong (tidak menimpa)
            if (empty($produk->foto_url) && empty($produk->foto)) {
                $produk->update(['foto_url' => $fotoUrl]);
                $this->info("✅ Baris {$baris}: [{$umkm->nama_usaha}] → foto_url diisi.");
                $diupdate++;
            } else {
                $dilewati++;
            }
        }

        fclose($handle);

        $this->newLine();
        $this->info("================================");
        $this->info("✅ Berhasil diupdate    : {$diupdate}");
        $this->info("⏭️  Dilewati            : {$dilewati}");
        $this->info("❌ UMKM tidak ditemukan : {$tidakketemu}");

        if ($diupdate > 0) {
            $this->newLine();
            if ($this->confirm('Download semua foto dari Drive ke server sekarang?', true)) {
                $this->call('umkm:download-foto-drive');
            }
        }
    }
}