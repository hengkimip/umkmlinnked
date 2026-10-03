<?php
// app/Policies/UmkmPolicy.php
namespace App\Policies;

use App\Models\Umkm;
use App\Models\User;

class UmkmPolicy
{
    public function before(User $user): ?bool
    {
        if ($user->hasRole('super-admin')) return true;
        return null;
    }

    public function viewAny(User $user): bool
    {
        return $user->hasRole(['admin-opd', 'super-admin']);
    }

    public function view(User $user, Umkm $umkm): bool
    {
        return $user->hasRole('super-admin')
            || $umkm->opd_id === $user->opd_id;
    }

    public function create(User $user): bool
    {
        return $user->hasRole(['admin-opd', 'super-admin']);
    }

    public function update(User $user, Umkm $umkm): bool
    {
        return $user->hasRole('super-admin')
            || $umkm->opd_id === $user->opd_id;
    }

    public function delete(User $user, Umkm $umkm): bool
    {
        return $user->hasRole('super-admin')
            || $umkm->opd_id === $user->opd_id;
    }
}