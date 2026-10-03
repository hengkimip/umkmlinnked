<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Opd extends Model
{
    protected $table = 'opd';

    protected $fillable = [
        'nama_opd','kode_opd','kabupaten',
        'alamat','telepon','email','website','is_active',
    ];
    protected $casts = ['is_active' => 'boolean'];

    public function users()
    {
        return $this->hasMany(User::class);
    }
    public function umkm()
    {
        return $this->hasMany(Umkm::class);
    }
}