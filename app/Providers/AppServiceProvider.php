<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\Keuangan;
use App\Models\Legalitas;
use App\Models\Pemasaran;
use App\Models\Produk;
use App\Models\ProfilUmkm;
use App\Models\Umkm;
use App\Models\User;
use App\Policies\UmkmPolicy;
use App\Support\CacheData;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // NFR-01: semua URL memakai HTTPS di produksi
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        // NFR-01: kebijakan kata sandi; lebih ketat di produksi
        Password::defaults(fn () => $this->app->environment('production')
            ? Password::min(10)->letters()->mixedCase()->numbers()
            : Password::min(8));

        // ==================== POLICIES ====================
        Gate::policy(Umkm::class, UmkmPolicy::class);

        // Cache data UMKM (beranda, statistik, peta) dibatalkan setiap kali datanya berubah
        foreach ([Umkm::class, Produk::class, Pemasaran::class, Legalitas::class, Keuangan::class, ProfilUmkm::class] as $model) {
            $model::saved(fn () => CacheData::segarkan());
            $model::deleted(fn () => CacheData::segarkan());
        }

        // ==================== ROLE-BASED GATES ====================
        
        /**
         * Super Admin Gate - using Spatie Permission
         */
        Gate::define('is-superadmin', function (User $user) {
            return $user->isSuperAdmin();
        });

        /**
         * Regular Admin Gate
         */
        Gate::define('is-admin', function (User $user) {
            return $user->isAdmin();
        });

        /**
         * Any Admin (Super or Regular)
         */
        Gate::define('is-any-admin', function (User $user) {
            return $user->isAdminAny();
        });

        /**
         * Access Peta Interaktif - SuperAdmin only
         */
        Gate::define('access-peta', function (User $user) {
            return $user->isSuperAdmin();
        });

        /**
         * Access Dashboard - All admins
         */
        Gate::define('access-dashboard', function (User $user) {
            return $user->isAdminAny();
        });
    }
}