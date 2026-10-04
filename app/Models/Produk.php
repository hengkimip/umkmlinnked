<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Support\Thumbnail;
use Illuminate\Support\Facades\Storage;

class Produk extends Model
{
    protected $table = 'produk';

    /**
     * Badge status/promosi produk. "unggulan" sekaligus menandai foto utama
     * (is_unggulan) — hanya satu per UMKM.
     */
    public const BADGE = [
        'unggulan'    => 'Unggulan',
        'terbaru'     => 'Terbaru',
        'promo'       => 'Promo',
        'terlaris'    => 'Terlaris',
        'rekomendasi' => 'Rekomendasi',
    ];

    protected $fillable = [
        'umkm_id', 'nama_produk', 'deskripsi', 'harga', 'satuan',
        'kapasitas_produksi', 'satuan_kapasitas',
        'foto', 'foto_url', 'urutan',
        'is_unggulan', 'badge', 'is_active',
    ];

    protected $casts = [
        'is_unggulan' => 'boolean',
        'is_active'   => 'boolean',
        'harga'       => 'float',
    ];

    public function umkm()
    {
        return $this->belongsTo(Umkm::class);
    }

    public function getBadgeLabelAttribute(): ?string
    {
        return self::BADGE[$this->badge] ?? null;
    }

    /**
     * Jadikan produk ini satu-satunya foto utama (unggulan) di UMKM-nya.
     */
    public function jadikanUtama(): void
    {
        $lain = fn () => static::where('umkm_id', $this->umkm_id)->whereKeyNot($this->getKey());
        $lain()->where('is_unggulan', true)->update(['is_unggulan' => false]);
        $lain()->where('badge', 'unggulan')->update(['badge' => null]);

        $this->forceFill(['is_unggulan' => true, 'badge' => 'unggulan'])->save();
    }

    /**
     * Hapus file foto lokal (bila ada) dari disk public.
     */
    public function hapusFileFoto(): void
    {
        if ($this->foto && Storage::disk('public')->exists($this->foto)) {
            Storage::disk('public')->delete($this->foto);
        }
        Thumbnail::hapus($this->foto);
        $this->cacheFoto = [];
    }

    /** Hasil cek file per instance, agar disk tidak diperiksa berulang dalam satu render. */
    private array $cacheFoto = [];

    /**
     * Accessor WAJIB ADA — dipanggil sebagai $produk->foto_final (foto ukuran asli)
     */
    public function getFotoFinalAttribute(): ?string
    {
        return $this->cacheFoto['final'] ??= $this->hitungFotoFinal();
    }

    /**
     * Foto ringan untuk kartu & daftar: thumbnail WebP bila ada, selain itu foto asli.
     */
    public function getFotoKecilAttribute(): ?string
    {
        if (! array_key_exists('kecil', $this->cacheFoto)) {
            $thumb = $this->foto ? Thumbnail::path($this->foto) : null;
            $this->cacheFoto['kecil'] = $thumb && Storage::disk('public')->exists($thumb)
                ? Storage::url($thumb)
                : $this->foto_final;
        }
        return $this->cacheFoto['kecil'];
    }

    private function hitungFotoFinal(): ?string
    {
        if ($this->foto && Storage::disk('public')->exists($this->foto)) {
            return Storage::url($this->foto);
        }

        if ($this->foto_url) {
            return $this->konversiDriveUrl($this->foto_url);
        }

        return null;
    }

    private function konversiDriveUrl(string $url): string
    {
        if (preg_match('/drive\.google\.com\/file\/d\/([a-zA-Z0-9_-]+)/', $url, $m)) {
            return "https://drive.google.com/uc?export=view&id={$m[1]}";
        }
        if (preg_match('/[?&]id=([a-zA-Z0-9_-]+)/', $url, $m)) {
            return "https://drive.google.com/uc?export=view&id={$m[1]}";
        }
        return $url;
    }
}