<?php
namespace App\Support;

use Illuminate\Support\Facades\Storage;

/**
 * Thumbnail WebP untuk foto produk (dipakai di kartu & daftar; foto asli tetap untuk
 * tampilan besar di halaman detail). Foto 1 MB menjadi ±20–40 KB.
 */
final class Thumbnail
{
    public const LEBAR    = 480;   // cukup tajam untuk kartu ±250px di layar retina
    public const KUALITAS = 76;
    private const MAKS_PIKSEL = 40_000_000; // lewati gambar raksasa agar memori aman

    public static function path(string $foto): string
    {
        return 'produk/thumb/' . pathinfo($foto, PATHINFO_FILENAME) . '.webp';
    }

    /**
     * Buat thumbnail untuk foto di disk public. Mengembalikan path thumbnail atau null.
     */
    public static function buat(string $foto, bool $timpa = false): ?string
    {
        $disk = Storage::disk('public');
        $tujuan = self::path($foto);

        if (! $timpa && $disk->exists($tujuan)) {
            return $tujuan;
        }
        if (! function_exists('imagewebp') || ! $disk->exists($foto)) {
            return null;
        }

        $info = @getimagesize($disk->path($foto));
        if (! $info || $info[0] * $info[1] > self::MAKS_PIKSEL) {
            return null;
        }

        $sumber = @imagecreatefromstring($disk->get($foto));
        if (! $sumber) {
            return null;
        }

        [$w, $h] = [imagesx($sumber), imagesy($sumber)];
        $lebar  = min(self::LEBAR, $w);
        $tinggi = (int) round($h * $lebar / $w);

        $hasil = imagecreatetruecolor($lebar, $tinggi);
        imagealphablending($hasil, false);
        imagesavealpha($hasil, true);
        imagecopyresampled($hasil, $sumber, 0, 0, 0, 0, $lebar, $tinggi, $w, $h);

        ob_start();
        imagewebp($hasil, null, self::KUALITAS);
        $disk->put($tujuan, ob_get_clean());

        imagedestroy($sumber);
        imagedestroy($hasil);

        return $tujuan;
    }

    public static function hapus(?string $foto): void
    {
        if ($foto) {
            Storage::disk('public')->delete(self::path($foto));
        }
    }
}
