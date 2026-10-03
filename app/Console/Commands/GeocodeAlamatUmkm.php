<?php
namespace App\Console\Commands;

use App\Models\Umkm;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class GeocodeAlamatUmkm extends Command
{
    protected $signature   = 'umkm:geocode {--debug : Test beberapa query saja dengan output detail}';
    protected $description = 'Konversi alamat_usaha menjadi latitude/longitude via Nominatim (OpenStreetMap)';

    public function handle(): void
    {
        if ($this->option('debug')) {
            $this->debugSatuAlamat();
            return;
        }

        $umkm = Umkm::whereNull('latitude')
            ->orWhereNull('longitude')
            ->get();

        if ($umkm->isEmpty()) {
            $this->info('Semua UMKM sudah punya koordinat.');
            return;
        }

        $this->info("Akan geocode {$umkm->count()} alamat.");
        $bar = $this->output->createProgressBar($umkm->count());

        $berhasil = 0;
        $gagal    = 0;

        foreach ($umkm as $u) {
            try {
                $query = $this->bangunQuerySederhana($u);

                $response = Http::withHeaders([
                    'User-Agent' => 'UMKMLinkedID/1.0 (kontak: admin@umkmlinked.id)',
                ])->timeout(15)->get('https://nominatim.openstreetmap.org/search', [
                    'q'      => $query,
                    'format' => 'json',
                    'limit'  => 1,
                ]);

                if (!$response->successful()) {
                    $gagal++;
                    $bar->advance();
                    sleep(1);
                    continue;
                }

                $hasil = $response->json();

                if (!empty($hasil)) {
                    $u->update([
                        'latitude'  => $hasil[0]['lat'],
                        'longitude' => $hasil[0]['lon'],
                    ]);
                    $berhasil++;
                } else {
                    $gagal++;
                    $this->newLine();
                    $this->warn("Tidak ketemu: {$u->nama_usaha} — query: {$query}");
                }
            } catch (\Throwable $e) {
                $gagal++;
                $this->newLine();
                $this->error("EXCEPTION {$u->nama_usaha}: " . $e->getMessage());
            }

            $bar->advance();
            sleep(1);
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("✅ Berhasil: {$berhasil}  |  ❌ Gagal: {$gagal}");
    }

    /**
     * Bangun query SEDERHANA — hanya nama jalan utama + kota + provinsi.
     * Kelurahan/kecamatan/RT-RW SENGAJA dibuang karena terbukti membuat
     * Nominatim gagal parsing untuk alamat Kalimantan Barat.
     */
    private function bangunQuerySederhana(Umkm $u): string
    {
        $alamat = $u->alamat_usaha ?? '';

        // Ambil bagian sebelum koma pertama — biasanya berisi nama jalan
        $bagianJalan = trim(explode(',', $alamat)[0]);

        // Buang kata "gang"/"gg" dan seterusnya jika ada, ambil hanya prefix "Jl./Jalan"
        // Contoh: "Jl. Ujung pandang 1" tetap; "Jl karet komplek surya kencana 5" jadi "Jl karet"
        if (preg_match('/^(Jl\.?|Jalan)\s+([A-Za-z\s]+?)(\s+\d|\s+gg\.?|\s+gang|\s+komp\.?|\s+komplek|,|$)/i', $bagianJalan, $m)) {
            $namaJalan = trim($m[1] . ' ' . $m[2]);
        } else {
            // Fallback: kalau tidak ada pola "Jl./Jalan", pakai kabupaten saja
            $namaJalan = null;
        }

        $kota = $u->kabupaten !== 'Tidak Diketahui' ? $u->kabupaten : 'Kalimantan Barat';

        if ($namaJalan) {
            return "{$namaJalan}, {$kota}, Kalimantan Barat, Indonesia";
        }

        // Tidak ada nama jalan terdeteksi (misal alamatnya "Desa Jeruju Besar")
        // → pakai kabupaten saja, sama seperti fallback centroid
        return "{$kota}, Kalimantan Barat, Indonesia";
    }

    private function debugSatuAlamat(): void
    {
        $sampel = Umkm::whereNotNull('alamat_usaha')->limit(10)->get();

        foreach ($sampel as $u) {
            $query = $this->bangunQuerySederhana($u);
            $this->line("Nama: {$u->nama_usaha}");
            $this->line("Query: {$query}");

            try {
                $response = Http::withHeaders([
                    'User-Agent' => 'UMKMLinkedID/1.0 (kontak: admin@umkmlinked.id)',
                ])->timeout(15)->get('https://nominatim.openstreetmap.org/search', [
                    'q'      => $query,
                    'format' => 'json',
                    'limit'  => 1,
                ]);

                $body = $response->json();
                $this->line('Hasil: ' . (empty($body) ? '❌ KOSONG' : '✅ KETEMU'));
            } catch (\Throwable $e) {
                $this->error('EXCEPTION: ' . $e->getMessage());
            }

            $this->newLine();
            sleep(1);
        }
    }
}