<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Opd extends Model
{
    protected $table = 'opd';

    // Wilayah kerja OPD: tingkat provinsi, atau salah satu kota/kabupaten (kolom "kabupaten")
    public const PROVINSI = 'Provinsi';
    public const WILAYAH_PROVINSI = [self::PROVINSI => 'Provinsi Kalimantan Barat'];
    public const WILAYAH = self::WILAYAH_PROVINSI + Umkm::KABUPATEN_LENGKAP;

    protected $fillable = [
        'nama_opd','kode_opd','kabupaten',
        'alamat','telepon','email','website','is_active','maks_admin',
    ];
    protected $casts = ['is_active' => 'boolean', 'maks_admin' => 'integer'];

    /** Nama wilayah kerja, mis. "Provinsi Kalimantan Barat" atau "Kab. Sambas". */
    public function wilayahLabel(): string
    {
        return self::WILAYAH[$this->kabupaten] ?? (string) $this->kabupaten;
    }

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