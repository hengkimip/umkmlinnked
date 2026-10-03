<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\Umkm;
use App\Models\User;
use App\Policies\UmkmPolicy;
use Illuminate\Support\Facades\Gate;

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
        // ==================== POLICIES ====================
        Gate::policy(Umkm::class, UmkmPolicy::class);

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