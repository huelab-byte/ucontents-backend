<?php

declare(strict_types=1);

namespace Modules\MediaUpload\Policies;

use Modules\MediaUpload\Models\MediaUploadFolder;
use Modules\UserManagement\Models\User;

class MediaUploadFolderPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('view_media_upload_folders') || $user->hasPermission('manage_media_upload_folders');
    }

    public function view(User $user, MediaUploadFolder $folder): bool
    {
        if ($folder->user_id !== $user->id) {
            return false;
        }
        return $user->hasPermission('view_media_upload_folders') || $user->hasPermission('manage_media_upload_folders');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('manage_media_upload_folders');
    }

    public function update(User $user, MediaUploadFolder $folder): bool
    {
        return $folder->user_id === $user->id && $user->hasPermission('manage_media_upload_folders');
    }

    public function delete(User $user, MediaUploadFolder $folder): bool
    {
        return $folder->user_id === $user->id && $user->hasPermission('manage_media_upload_folders');
    }
}
