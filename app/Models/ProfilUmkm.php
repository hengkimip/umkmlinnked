<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Jawaban kuesioner profil UMKM (teks asli dari formulir/import).
 */
class ProfilUmkm extends Model
{
    use LogsActivity;

    protected $table = 'profil_umkm';

    protected $fillable = [
        'umkm_id', 'program_bi', 'produk_lainnya', 'kapasitas_produk_lainnya',
        'saluran_pemasaran', 'marketplace', 'bentuk_legalitas', 'sertifikasi_produk',
        'metode_pencatatan', 'pembiayaan_2026', 'pembiayaan_diterima', 'rencana_pembiayaan',
        'rekomendasi_program',
    ];

    protected $casts = [
        'rekomendasi_program' => 'array',
    ];

    /**
     * Program yang pernah ditetapkan untuk UMKM lain, beserta jumlah UMKM pemakainya
     * (terbanyak dulu) — dipakai sebagai pilihan cepat saat menetapkan program.
     *
     * @return array<string, int> nama program => jumlah UMKM
     */
    public static function usulanProgram(?int $kecualiUmkmId = null): array
    {
        $hitung = [];
        static::query()
            ->whereNotNull('rekomendasi_program')
            ->when($kecualiUmkmId, fn ($q) => $q->where('umkm_id', '!=', $kecualiUmkmId))
            ->pluck('rekomendasi_program')
            ->each(function ($daftar) use (&$hitung) {
                foreach ((array) $daftar as $program) {
                    $hitung[$program] = ($hitung[$program] ?? 0) + 1;
                }
            });

        uksort($hitung, fn ($a, $b) => [$hitung[$b], $a] <=> [$hitung[$a], $b]);

        return $hitung;
    }

    public function umkm()
    {
        return $this->belongsTo(Umkm::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty()->dontSubmitEmptyLogs();
    }
}
