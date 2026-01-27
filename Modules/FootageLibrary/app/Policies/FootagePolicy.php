<?php

declare(strict_types=1);

namespace Modules\FootageLibrary\Policies;

use Modules\FootageLibrary\Models\Footage;
use Modules\UserManagement\Models\User;

class FootagePolicy
{
    /**
     * Determine if the user can view any footage.
     */
    public function viewAny(User $user): bool
    {
        if ($user->isAdmin()) {
            return $user->hasPermission('view_all_footage');
        }
        return $user->hasPermission('view_footage');
    }

    /**
     * Determine if the user can view the footage.
     */
    public function view(User $user, Footage $footage): bool
    {
        if ($user->isAdmin()) {
            return $user->hasPermission('view_all_footage');
        }
        return $footage->user_id === $user->id && $user->hasPermission('view_footage');
    }

    /**
     * Determine if the user can create footage.
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('upload_footage');
    }

    /**
     * Determine if the user can update the footage.
     */
    public function update(User $user, Footage $footage): bool
    {
        if ($user->isAdmin()) {
            return $user->hasPermission('delete_any_footage');
        }
        return $footage->user_id === $user->id && $user->hasPermission('manage_footage');
    }

    /**
     * Determine if the user can delete the footage.
     */
    public function delete(User $user, Footage $footage): bool
    {
        if ($user->isAdmin()) {
            return $user->hasPermission('delete_any_footage');
        }
        return $footage->user_id === $user->id && $user->hasPermission('manage_footage');
    }
}
