<?php

declare(strict_types=1);

namespace Modules\VideoOverlay\Policies;

use Modules\VideoOverlay\Models\VideoOverlay;
use Modules\VideoOverlay\Models\VideoOverlayFolder;
use Modules\UserManagement\Models\User;

class VideoOverlayPolicy
{
    /**
     * Determine if the user can view any video overlays.
     */
    public function viewAny(User $user): bool
    {
        if ($user->isAdmin()) {
            return $user->hasPermission('view_all_video_overlay');
        }
        return $user->hasPermission('view_video_overlay');
    }

    /**
     * Determine if the user can view the video overlay.
     */
    public function view(User $user, VideoOverlay $videoOverlay): bool
    {
        if ($user->isAdmin()) {
            return $user->hasPermission('view_all_video_overlay');
        }
        return $videoOverlay->user_id === $user->id && $user->hasPermission('view_video_overlay');
    }

    /**
     * Determine if the user can create video overlays.
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('upload_video_overlay');
    }

    /**
     * Determine if the user can update the video overlay.
     */
    public function update(User $user, VideoOverlay $videoOverlay): bool
    {
        if ($user->isAdmin()) {
            return $user->hasPermission('delete_any_video_overlay');
        }
        return $videoOverlay->user_id === $user->id && $user->hasPermission('manage_video_overlay');
    }

    /**
     * Determine if the user can delete the video overlay.
     */
    public function delete(User $user, VideoOverlay $videoOverlay): bool
    {
        if ($user->isAdmin()) {
            return $user->hasPermission('delete_any_video_overlay');
        }
        return $videoOverlay->user_id === $user->id && $user->hasPermission('manage_video_overlay');
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
        return $user->hasPermission('manage_video_overlay_folders');
    }

    /**
     * Determine if the user can update the folder.
     */
    public function updateFolder(User $user, VideoOverlayFolder $folder): bool
    {
        if ($user->isAdmin()) {
            return $user->hasPermission('delete_any_video_overlay');
        }
        return $folder->user_id === $user->id && $user->hasPermission('manage_video_overlay_folders');
    }

    /**
     * Determine if the user can delete the folder.
     */
    public function deleteFolder(User $user, VideoOverlayFolder $folder): bool
    {
        if ($user->isAdmin()) {
            return $user->hasPermission('delete_any_video_overlay');
        }
        return $folder->user_id === $user->id && $user->hasPermission('manage_video_overlay_folders');
    }
}
