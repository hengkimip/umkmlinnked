<?php
namespace App\Console\Commands;

use App\Models\Produk;
use App\Support\Thumbnail;
use Illuminate\Console\Command;

/**
 * Buat thumbnail WebP untuk foto produk yang sudah ada (foto baru dibuatkan otomatis saat diunggah).
 */
class BuatThumbnailProduk extends Command
{
    protected $signature = 'produk:thumbnail {--timpa : Buat ulang walau thumbnail sudah ada}';

    protected $description = 'Buat thumbnail WebP ringan untuk semua foto produk';

    public function handle(): int
    {
        $produk = Produk::whereNotNull('foto')->pluck('foto');
        $berhasil = 0;
        $gagal = [];

        $this->withProgressBar($produk, function (string $foto) use (&$berhasil, &$gagal) {
            Thumbnail::buat($foto, (bool) $this->option('timpa')) ? $berhasil++ : $gagal[] = $foto;
        });

        $this->newLine(2);
        $this->info("{$berhasil} thumbnail siap.");
        foreach ($gagal as $f) {
            $this->warn("Dilewati (file hilang/tidak terbaca): {$f}");
        }

        return self::SUCCESS;
    }
}
