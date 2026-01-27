<?php

declare(strict_types=1);

namespace Modules\UserManagement\Policies;

use Modules\UserManagement\Models\Permission;
use Modules\UserManagement\Models\User;

/**
 * Policy for Permission authorization
 */
class PermissionPolicy
{
    /**
     * Determine if the user can view any permissions.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->hasPermission('view_permissions');
    }

    /**
     * Determine if the user can view the permission.
     */
    public function view(User $user, Permission $permission): bool
    {
        return $user->isAdmin() || $user->hasPermission('view_permissions');
    }

    /**
     * Determine if the user can create permissions.
     */
    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->hasPermission('manage_permissions');
    }

    /**
     * Determine if the user can update the permission.
     */
    public function update(User $user, Permission $permission): bool
    {
        return $user->isAdmin() || $user->hasPermission('manage_permissions');
    }

    /**
     * Determine if the user can delete the permission.
     */
    public function delete(User $user, Permission $permission): bool
    {
        return $user->isAdmin() || $user->hasPermission('manage_permissions');
    }
}
