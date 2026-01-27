<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

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
        $this->configureGate();
    }

    /**
     * Configure Gate for authorization.
     */
    private function configureGate(): void
    {
        // Super Admin bypass - always allow all actions for system super admin
        Gate::before(function ($user, $ability) {
            // Ensure user is authenticated
            if (!$user) {
                return null;
            }

            // Check if user is a system super admin (has super_admin role)
            if ($user->is_system && $user->hasRole('super_admin')) {
                return true;
            }

            return null; // Continue to other checks
        });

        // Register all permissions from config as Gate abilities
        $permissionsPath = base_path('Modules/Core/config/permissions.php');
        if (file_exists($permissionsPath)) {
            $permissions = require $permissionsPath;
            foreach (array_keys($permissions) as $permissionSlug) {
                Gate::define($permissionSlug, function ($user) use ($permissionSlug) {
                    return $user->hasPermission($permissionSlug);
                });
            }
        }
    }
}
