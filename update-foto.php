<?php
// Jalankan: php artisan tinker --execute="require 'update-foto.php';"

use App\Models\Produk;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

echo "=== UPDATE FOTO DARI DRIVE ===\n\n";

$produk = Produk::whereNotNull('foto_url')
                 ->whereNull('foto')
                 ->get();

echo "Produk dengan foto_url tapi belum didownload: {$produk->count()}\n\n";

if ($produk->isEmpty()) {
    echo "Tidak ada yang perlu diproses.\n";
    echo "Kemungkinan kolom foto_url kosong di database.\n";
    echo "Cek dengan: Produk::whereNotNull('foto_url')->count()\n";
    return;
}

$berhasil = 0;
$gagal    = 0;

foreach ($produk as $p) {
    try {
        // Konversi URL Drive
        $url = $p->foto_url;
        if (preg_match('/drive\.google\.com\/file\/d\/([a-zA-Z0-9_-]+)/', $url, $m)) {
            $url = "https://drive.google.com/uc?export=download&id={$m[1]}&confirm=t";
        }

        $response = Http::timeout(30)
            ->withHeaders(['User-Agent' => 'Mozilla/5.0'])
            ->get($url);

        if (!$response->successful()) {
            throw new Exception("HTTP {$response->status()}");
        }

        $contentType = $response->header('Content-Type');
        $ext = str_contains($contentType, 'png') ? 'png' :
               (str_contains($contentType, 'webp') ? 'webp' : 'jpg');

        $namaFile = 'produk/foto/' . Str::slug($p->nama_produk) . '-' . $p->id . '.' . $ext;

        Storage::disk('public')->put($namaFile, $response->body());
        $p->update(['foto' => $namaFile]);

        echo "✅ ID:{$p->id} → {$namaFile}\n";
        $berhasil++;

        usleep(500000);

    } catch (\Throwable $e) {
        echo "❌ ID:{$p->id}: {$e->getMessage()}\n";
        $gagal++;
    }
}

echo "\nBerhasil: {$berhasil} | Gagal: {$gagal}\n";