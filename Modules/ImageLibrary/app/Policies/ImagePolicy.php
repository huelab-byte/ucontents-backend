<?php

declare(strict_types=1);

namespace Modules\ImageLibrary\Policies;

use Modules\ImageLibrary\Models\Image;
use Modules\UserManagement\Models\User;

class ImagePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('view_image') || $user->hasPermission('view_all_image') || $user->hasPermission('use_image_library');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Image $image): bool
    {
        if ($user->hasPermission('view_all_image')) {
            return true;
        }

        if ($user->hasPermission('view_image') && $image->user_id === $user->id) {
            return true;
        }

        return $user->hasPermission('use_image_library') && $image->status === 'ready';
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('upload_image');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Image $image): bool
    {
        if ($user->hasPermission('view_all_image')) {
            return true;
        }

        return $user->hasPermission('manage_image') && $image->user_id === $user->id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Image $image): bool
    {
        if ($user->hasPermission('delete_any_image')) {
            return true;
        }

        return $user->hasPermission('manage_image') && $image->user_id === $user->id;
    }
}
