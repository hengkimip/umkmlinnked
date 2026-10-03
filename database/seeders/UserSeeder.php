<?php

namespace Database\Seeders;

use App\Models\Opd;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    /**
     * Membuat akun awal. Kata sandi diambil dari .env
     * (SEED_SUPERADMIN_PASSWORD, SEED_ADMIN_OPD_PASSWORD); jika kosong,
     * dibuat acak dan ditampilkan sekali di konsol. Tidak ada kata sandi bawaan
     * yang ditulis di kode (NFR-01).
     */
    public function run(): void
    {
        $this->buatAkun(
            email: 'superadmin@umkmlinked.id',
            nama: 'Super Admin',
            jabatan: 'Super Administrator',
            role: User::ROLE_SUPER_ADMIN,
            envPassword: 'SEED_SUPERADMIN_PASSWORD',
        );

        $this->buatAkun(
            email: 'admin@umkmlinked.id',
            nama: 'Admin OPD',
            jabatan: 'Administrator OPD',
            role: User::ROLE_ADMIN_OPD,
            envPassword: 'SEED_ADMIN_OPD_PASSWORD',
            opdId: Opd::query()->value('id'),
        );
    }

    private function buatAkun(string $email, string $nama, string $jabatan, string $role, string $envPassword, ?int $opdId = null): void
    {
        $user = User::where('email', $email)->first();

        if (! $user) {
            $password = env($envPassword) ?: Str::password(16);

            $user = User::create([
                'name'      => $nama,
                'email'     => $email,
                'password'  => $password,
                'jabatan'   => $jabatan,
                'opd_id'    => $opdId,
                'is_active' => true,
            ]);
            $user->forceFill(['email_verified_at' => now()])->save();

            $this->command?->warn("Akun {$role}: {$email} / {$password}  (segera ganti setelah login)");
        }

        $user->syncRoles([$role]);
    }
}
