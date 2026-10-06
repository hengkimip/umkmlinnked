<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Satu entri antrean review "kemungkinan duplikat" (lihat DeteksiDuplikatService).
 */
class UmkmDuplikat extends Model
{
    public const MENUNGGU    = 'menunggu';
    public const DIGABUNG    = 'digabung';     // admin: "Usaha yang sama" → data lama diperbarui
    public const DIBUAT_BARU = 'dibuat_baru';  // admin: "Usaha berbeda" → disimpan sebagai UMKM baru
    public const DIHAPUS     = 'dihapus';      // admin: "Hapus data usulan baru" → tidak disimpan sama sekali

    protected $table = 'umkm_duplikat';

    protected $fillable = [
        'umkm_id', 'opd_id', 'user_id', 'sumber', 'data', 'baris', 'skor', 'rincian',
        'status', 'diputuskan_oleh', 'diputuskan_pada', 'umkm_hasil_id',
    ];

    protected $casts = [
        'data'            => 'array',
        'baris'           => 'array',
        'rincian'         => 'array',
        'skor'            => 'integer',
        'diputuskan_pada' => 'datetime',
    ];

    public function umkm()
    {
        return $this->belongsTo(Umkm::class)->withTrashed();
    }

    public function opd()
    {
        return $this->belongsTo(Opd::class);
    }

    public function pengunggah()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function pemutus()
    {
        return $this->belongsTo(User::class, 'diputuskan_oleh');
    }

    public function scopeMenunggu(Builder $q): Builder
    {
        return $q->where('status', self::MENUNGGU);
    }

    /**
     * Entri yang boleh dilihat pengguna (FR-02): Super Admin semua; Admin OPD bila data baru
     * ditujukan ke OPD-nya atau UMKM pembandingnya binaan OPD-nya.
     */
    public function scopeTerlihatOleh(Builder $q, User $user): Builder
    {
        if ($user->isSuperAdmin()) {
            return $q;
        }

        return $q->where(fn ($w) => $w->where('opd_id', $user->opd_id)
            ->orWhereHas('umkm', fn ($u) => $u->withTrashed()->where('opd_id', $user->opd_id)));
    }
}
