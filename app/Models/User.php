<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
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
     * Halaman tujuan setelah login (FR-16):
     * super-admin → peta interaktif, selain itu → dashboard admin.
     */
    public function homeUrl(): string
    {
        return $this->isSuperAdmin()
            ? route('admin.peta-interaktif', absolute: false)
            : route('admin.dashboard', absolute: false);
    }
}
