<?php

declare(strict_types=1);

namespace Modules\ImageOverlay\Policies;

use Modules\ImageOverlay\Models\ImageOverlay;
use Modules\ImageOverlay\Models\ImageOverlayFolder;
use Modules\UserManagement\Models\User;

class ImageOverlayPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('view_image_overlay') || $user->hasPermission('view_all_image_overlay');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, ImageOverlay $imageOverlay): bool
    {
        if ($user->hasPermission('view_all_image_overlay')) {
            return true;
        }

        return $user->hasPermission('view_image_overlay') && $imageOverlay->user_id === $user->id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('upload_image_overlay');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, ImageOverlay $imageOverlay): bool
    {
        if ($user->hasPermission('view_all_image_overlay')) {
            return true;
        }

        return $user->hasPermission('manage_image_overlay') && $imageOverlay->user_id === $user->id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, ImageOverlay $imageOverlay): bool
    {
        if ($user->hasPermission('delete_any_image_overlay')) {
            return true;
        }

        return $user->hasPermission('manage_image_overlay') && $imageOverlay->user_id === $user->id;
    }

    /**
     * Determine if the user can view any folders.
     */
    public function viewAnyFolder(User $user): bool
    {
        return $this->viewAny($user);
    }

    /**
     * Determine if the user can create folders.
     */
    public function createFolder(User $user): bool
    {
        return $user->hasPermission('manage_image_overlay_folders');
    }

    /**
     * Determine if the user can update the folder.
     */
    public function updateFolder(User $user, ImageOverlayFolder $folder): bool
    {
        if ($user->hasPermission('delete_any_image_overlay')) {
            return true;
        }
        return $folder->user_id === $user->id && $user->hasPermission('manage_image_overlay_folders');
    }

    /**
     * Determine if the user can delete the folder.
     */
    public function deleteFolder(User $user, ImageOverlayFolder $folder): bool
    {
        if ($user->hasPermission('delete_any_image_overlay')) {
            return true;
        }
        return $folder->user_id === $user->id && $user->hasPermission('manage_image_overlay_folders');
    }
}
