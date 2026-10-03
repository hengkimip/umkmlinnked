<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Pembiayaan extends Model
{
    protected $table = 'pembiayaan';
    protected $fillable = [
        'umkm_id','nama_bank_lembaga','jenis_pembiayaan',
        'jumlah_pinjaman','bunga_persen','tanggal_mulai',
        'tanggal_selesai','status',
    ];
    public function umkm() { return $this->belongsTo(Umkm::class); }
}