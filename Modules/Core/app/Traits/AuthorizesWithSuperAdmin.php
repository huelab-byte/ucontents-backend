<?php

declare(strict_types=1);

namespace Modules\Core\Traits;

/**
 * Trait for FormRequests to check authorization with superadmin bypass
 */
trait AuthorizesWithSuperAdmin
{
    /**
     * Check if user has permission, with superadmin bypass
     */
    protected function hasPermission(string $permission): bool
    {
        $user = $this->user();
        
        if (!$user) {
            return false;
        }
        
        // Get fresh user with roles loaded
        $freshUser = \Modules\UserManagement\Models\User::with('roles')->find($user->id);
        
        if (!$freshUser) {
            return false;
        }
        
        // Super admin bypass
        if ($freshUser->roles()->where('slug', 'super_admin')->exists()) {
            return true;
        }
        
        // Check permission
        return $freshUser->hasPermission($permission);
    }
}
