<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Legalitas extends Model
{
    protected $table = 'legalitas';
    protected $fillable = [
        'umkm_id','nomor_nib','tanggal_nib','nomor_siup','tanggal_siup',
        'nomor_tdp','nomor_npwp','nomor_akte','nomor_halal','expired_halal',
        'nomor_bpom','expired_bpom','nomor_pirt','expired_pirt','nomor_sni',
        'sertifikat_lain','file_nib','file_halal','file_bpom',
    ];
    protected $casts = ['sertifikat_lain'=>'array'];
    public function umkm() { return $this->belongsTo(Umkm::class); }
}