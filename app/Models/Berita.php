<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Berita official dari Super Admin. Tampil di /berita bila berstatus "terbit"
 * dan tanggal terbitnya sudah lewat (bisa dijadwalkan).
 */
class Berita extends Model
{
    public const STATUS = ['draft' => 'Draf', 'terbit' => 'Terbit'];

    protected $table = 'berita';

    protected $fillable = ['judul', 'slug', 'ringkasan', 'isi', 'gambar', 'status', 'terbit_pada', 'penulis_id'];

    protected $casts = ['terbit_pada' => 'datetime'];

    public function penulis()
    {
        return $this->belongsTo(User::class, 'penulis_id');
    }

    /** Berita yang sudah tampil untuk publik. */
    public function scopeTerbit(Builder $query): Builder
    {
        return $query->where('status', 'terbit')->where('terbit_pada', '<=', now());
    }

    public function sudahTampil(): bool
    {
        return $this->status === 'terbit' && $this->terbit_pada?->lte(now());
    }

    /** Slug unik dari judul, mis. "pelatihan-ekspor-2026" atau "pelatihan-ekspor-2026-2". */
    public static function slugUnik(string $judul, ?int $kecuali = null): string
    {
        $dasar = Str::limit(Str::slug($judul), 120, '') ?: 'berita';
        $slug  = $dasar;
        for ($i = 2; static::where('slug', $slug)->when($kecuali, fn ($q) => $q->whereKeyNot($kecuali))->exists(); $i++) {
            $slug = "{$dasar}-{$i}";
        }

        return $slug;
    }

    public function gambarUrl(): ?string
    {
        return $this->gambar ? Storage::disk('public')->url($this->gambar) : null;
    }

    /** Ringkasan untuk kartu & meta description: diisi manual, atau potongan isi. */
    public function cuplikan(int $panjang = 180): string
    {
        return $this->ringkasan ?: Str::limit(preg_replace('/\s+/', ' ', trim($this->isi)), $panjang);
    }

    /**
     * Isi berita sebagai paragraf. Teks biasa (tanpa HTML) — baris kosong memisahkan paragraf,
     * jadi aman dari XSS tanpa perlu penyunting HTML.
     *
     * @return list<string>
     */
    public function paragraf(): array
    {
        return array_values(array_filter(array_map('trim', preg_split('/\R\s*\R/', (string) $this->isi))));
    }
}
