<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Opd extends Model
{
    protected $table = 'opd';

    protected $fillable = [
        'nama_opd','kode_opd','kabupaten',
        'alamat','telepon','email','website','is_active','maks_admin',
    ];
    protected $casts = ['is_active' => 'boolean', 'maks_admin' => 'integer'];

    public function users()
    {
        return $this->hasMany(User::class);
    }

    /** Akun Admin OPD aktif — dibatasi maks_admin yang ditentukan Super Admin. */
    public function jumlahAdminAktif(): int
    {
        return $this->users()->where('is_active', true)->role(User::ROLE_ADMIN_OPD)->count();
    }
    public function umkm()
    {
        return $this->hasMany(Umkm::class);
    }
}