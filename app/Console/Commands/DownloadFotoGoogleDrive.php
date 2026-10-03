<?php
namespace App\Console\Commands;

use App\Models\Produk;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DownloadFotoGoogleDrive extends Command
{
    protected $signature   = 'umkm:download-foto-drive';
    protected $description = 'Download foto produk dari foto_url (Google Drive) ke storage lokal';

    public function handle(): void
    {
        $produk = Produk::whereNotNull('foto_url')
                        ->whereNull('foto')
                        ->get();

        if ($produk->isEmpty()) {
            $this->info('Tidak ada produk yang perlu didownload.');
            return;
        }

        $this->info("Mendownload {$produk->count()} foto...");
        $bar      = $this->output->createProgressBar($produk->count());
        $berhasil = 0;
        $gagal    = 0;

        foreach ($produk as $p) {
            try {
                $url = $this->konversiUrl($p->foto_url);

                $response = Http::timeout(30)
                    ->withHeaders(['User-Agent' => 'Mozilla/5.0'])
                    ->get($url);

                if (!$response->successful()) {
                    throw new \Exception("HTTP {$response->status()}");
                }

                $contentType = $response->header('Content-Type');
                $ext = str_contains($contentType, 'png') ? 'png' :
                       (str_contains($contentType, 'webp') ? 'webp' : 'jpg');

                $namaFile = 'produk/foto/' . Str::slug($p->nama_produk) . '-' . $p->id . '.' . $ext;

                Storage::disk('public')->put($namaFile, $response->body());
                $p->update(['foto' => $namaFile]);

                $berhasil++;
                usleep(500000);

            } catch (\Throwable $e) {
                $gagal++;
                $this->newLine();
                $this->warn("Gagal ID {$p->id}: {$e->getMessage()}");
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("✅ Berhasil: {$berhasil} | ❌ Gagal: {$gagal}");
    }

    private function konversiUrl(string $url): string
    {
        if (preg_match('/drive\.google\.com\/file\/d\/([a-zA-Z0-9_-]+)/', $url, $m)) {
            return "https://drive.google.com/uc?export=download&id={$m[1]}&confirm=t";
        }
        if (preg_match('/[?&]id=([a-zA-Z0-9_-]+)/', $url, $m)) {
            return "https://drive.google.com/uc?export=download&id={$m[1]}&confirm=t";
        }
        return $url;
    }
}