<?php
// app/Policies/UmkmPolicy.php
namespace App\Policies;

use App\Models\Umkm;
use App\Models\User;

class UmkmPolicy
{
    public function before(User $user): ?bool
    {
        if ($user->isSuperAdmin()) return true;
        return null;
    }

    public function viewAny(User $user): bool
    {
        return $user->isAdminAny();
    }

    public function view(User $user, Umkm $umkm): bool
    {
        return $this->dalamWilayah($user, $umkm);
    }

    public function create(User $user): bool
    {
        return $user->isAdminAny();
    }

    public function update(User $user, Umkm $umkm): bool
    {
        return $this->dalamWilayah($user, $umkm);
    }

    public function delete(User $user, Umkm $umkm): bool
    {
        return $this->dalamWilayah($user, $umkm);
    }

    /**
     * Admin OPD hanya boleh mengelola UMKM binaan OPD-nya (FR-02).
     */
    private function dalamWilayah(User $user, Umkm $umkm): bool
    {
        return $user->isAdmin()
            && $user->opd_id !== null
            && (int) $umkm->opd_id === (int) $user->opd_id;
    }
}
