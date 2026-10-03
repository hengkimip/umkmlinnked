<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PemilikUsaha extends Model
{
    use SoftDeletes;

    protected $table = 'pemilik_usaha';
    protected $fillable = [
        'nama_lengkap','nik','jenis_kelamin','tanggal_lahir',
        'alamat','kabupaten','kecamatan','telepon','email',
        'pendidikan','foto_ktp','foto_profil',
    ];

    public function umkm()
    {
        return $this->hasMany(Umkm::class, 'pemilik_usaha_id');
    }
}