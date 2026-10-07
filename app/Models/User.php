<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasRoles;

    // Nama role Spatie sesuai PRD v1.1 (FR-01)
    public const ROLE_SUPER_ADMIN = 'super-admin';
    public const ROLE_ADMIN_OPD   = 'admin-opd';

    protected $fillable = ['name', 'email', 'password', 'opd_id', 'jabatan', 'is_active'];
    protected $hidden   = ['password', 'remember_token'];

    public function opd() { return $this->belongsTo(Opd::class); }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'is_active'         => 'boolean',
        ];
    }

    // ==================== HELPER PERAN ====================

    public function isSuperAdmin(): bool
    {
        return $this->hasRole(self::ROLE_SUPER_ADMIN);
    }

    public function isAdmin(): bool
    {
        return $this->hasRole(self::ROLE_ADMIN_OPD);
    }

    public function isAdminAny(): bool
    {
        return $this->hasAnyRole([self::ROLE_SUPER_ADMIN, self::ROLE_ADMIN_OPD]);
    }

    /** Instansi pengguna: Super Admin → Bank Indonesia Wilayah Kalimantan Barat, Admin OPD → OPD-nya. */
    public function instansi(): ?string
    {
        return $this->isSuperAdmin() ? config('umkm.instansi_super_admin') : $this->opd?->nama_opd;
    }

    /**
     * Alasan akses admin ditolak (null = boleh): akun dinonaktifkan Super Admin,
     * atau OPD-nya sedang dinonaktifkan. Nilai kosong (belum diisi) dianggap aktif.
     */
    public function alasanAksesDitolak(): ?string
    {
        return match (true) {
            $this->is_active === false                         => 'Akun Anda dinonaktifkan. Hubungi Super Admin.',
            $this->isAdmin() && $this->opd?->is_active === false => 'Akses OPD Anda sedang dinonaktifkan oleh Super Admin.',
            default                                            => null,
        };
    }

    /** Jumlah maksimum Super Admin aktif (permintaan Bank Indonesia, 1–3 orang). */
    public static function maksSuperAdmin(): int
    {
        return (int) config('umkm.maks_super_admin', 3);
    }

    public static function jumlahSuperAdminAktif(): int
    {
        return static::role(self::ROLE_SUPER_ADMIN)->where('is_active', true)->count();
    }

    /**
     * Label peran untuk ditampilkan di UI.
     */
    public function roleLabel(): string
    {
        return match (true) {
            $this->isSuperAdmin() => 'Super Admin',
            $this->isAdmin()      => 'Admin OPD',
            default               => 'Tanpa Peran',
        };
    }

    /**
     * Halaman tujuan setelah login (FR-16): dashboard sesuai peran
     * (super-admin → /superadmin/dashboard, admin-opd → /admin/dashboard).
     */
    public function homeUrl(): string
    {
        return $this->dashboardUrl();
    }

    /**
     * Dashboard sesuai peran: Super Admin → /superadmin/dashboard, Admin OPD → /admin/dashboard.
     */
    public function dashboardUrl(): string
    {
        return $this->isSuperAdmin()
            ? route('superadmin.dashboard', absolute: false)
            : route('admin.dashboard', absolute: false);
    }

    /**
     * Akhiri sesi login akun ini di perangkat lain (kata sandi diganti/direset, akun dihapus):
     * hapus sesinya di database dan batalkan cookie "ingat saya". $kecuali = sesi yang dipertahankan.
     */
    public function akhiriSesi(?string $kecuali = null): void
    {
        if (config('session.driver') === 'database') {
            DB::table(config('session.table', 'sessions'))
                ->where('user_id', $this->id)
                ->when($kecuali, fn ($q) => $q->where('id', '!=', $kecuali))
                ->delete();
        }

        if ($this->exists) {
            $this->forceFill(['remember_token' => Str::random(60)])->saveQuietly();
        }
    }
}
