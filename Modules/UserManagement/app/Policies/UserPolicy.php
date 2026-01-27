<?php

declare(strict_types=1);

namespace Modules\UserManagement\Policies;

use Modules\UserManagement\Models\User;

/**
 * Policy for User authorization
 */
class UserPolicy
{
    /**
     * Determine if the user can view any users.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->hasPermission('view_users');
    }

    /**
     * Determine if the user can view the user.
     */
    public function view(User $user, User $model): bool
    {
        // Users can view their own profile, admins can view anyone
        return $user->id === $model->id || $user->isAdmin() || $user->hasPermission('view_users');
    }

    /**
     * Determine if the user can create users.
     */
    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->hasPermission('create_user');
    }

    /**
     * Determine if the user can update the user.
     */
    public function update(User $user, User $model): bool
    {
        // Users can update their own profile, admins can update anyone
        if ($user->id === $model->id) {
            return true; // Users can always update their own profile
        }

        return $user->isAdmin() || $user->hasPermission('update_user');
    }

    /**
     * Determine if the user can delete the user.
     */
    public function delete(User $user, User $model): bool
    {
        // Cannot delete yourself
        if ($user->id === $model->id) {
            return false;
        }

        return $user->isAdmin() || $user->hasPermission('delete_user');
    }
}
