<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Keuangan extends Model
{
    protected $table = 'keuangan';
    protected $fillable = [
        'umkm_id','tahun','omzet_tahunan','laba_bersih',
        'total_aset','total_modal','memiliki_pencatatan',
        'jenis_pencatatan','sudah_audit',
    ];
    protected $casts = [
        'memiliki_pencatatan' => 'boolean',
        'sudah_audit'         => 'boolean',
    ];
    public function umkm() { return $this->belongsTo(Umkm::class); }
}