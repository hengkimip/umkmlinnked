<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Produk extends Model
{
    protected $table = 'produk';

    protected $fillable = [
        'umkm_id', 'nama_produk', 'deskripsi', 'harga', 'satuan',
        'kapasitas_produksi', 'satuan_kapasitas',
        'foto', 'foto_url', 'urutan',
        'is_unggulan', 'is_active',
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

    /**
     * Accessor WAJIB ADA — dipanggil sebagai $produk->foto_final
     */
    public function getFotoFinalAttribute(): ?string
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