<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Pemasaran extends Model
{
    protected $table = 'pemasaran';
    protected $fillable = [
        'umkm_id','platform_online','memiliki_website',
        'jangkauan_pasar','provinsi_tujuan','negara_ekspor',
        'strategi_pemasaran','jumlah_reseller',
    ];
    protected $casts = [
        'platform_online'  => 'array',
        'provinsi_tujuan'  => 'array',
        'negara_ekspor'    => 'array',
        'memiliki_website' => 'boolean',
    ];
    public function umkm() { return $this->belongsTo(Umkm::class); }
}