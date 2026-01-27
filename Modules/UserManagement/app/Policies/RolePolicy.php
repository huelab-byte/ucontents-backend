<?php

declare(strict_types=1);

namespace Modules\UserManagement\Policies;

use Modules\UserManagement\Models\Role;
use Modules\UserManagement\Models\User;

/**
 * Policy for Role authorization
 */
class RolePolicy
{
    /**
     * Determine if the user can view any roles.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->hasPermission('view_roles');
    }

    /**
     * Determine if the user can view the role.
     */
    public function view(User $user, Role $role): bool
    {
        return $user->isAdmin() || $user->hasPermission('view_roles');
    }

    /**
     * Determine if the user can create roles.
     */
    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->hasPermission('create_role');
    }

    /**
     * Determine if the user can update the role.
     */
    public function update(User $user, Role $role): bool
    {
        if ($role->is_system) {
            // Only super admins can modify system roles
            return $user->hasRole('super_admin');
        }

        return $user->isAdmin() || $user->hasPermission('update_role');
    }

    /**
     * Determine if the user can delete the role.
     */
    public function delete(User $user, Role $role): bool
    {
        if ($role->is_system) {
            return false; // System roles cannot be deleted
        }

        return $user->isAdmin() || $user->hasPermission('delete_role');
    }
}
