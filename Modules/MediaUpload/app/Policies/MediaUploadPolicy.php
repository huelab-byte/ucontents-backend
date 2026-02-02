<?php

declare(strict_types=1);

namespace Modules\MediaUpload\Policies;

use Modules\MediaUpload\Models\MediaUpload;
use Modules\UserManagement\Models\User;

class MediaUploadPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('manage_media_uploads') || $user->hasPermission('upload_media');
    }

    public function view(User $user, MediaUpload $upload): bool
    {
        return $upload->user_id === $user->id && ($user->hasPermission('manage_media_uploads') || $user->hasPermission('upload_media'));
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('upload_media');
    }

    public function update(User $user, MediaUpload $upload): bool
    {
        return $upload->user_id === $user->id && $user->hasPermission('manage_media_uploads');
    }

    public function delete(User $user, MediaUpload $upload): bool
    {
        return $upload->user_id === $user->id && $user->hasPermission('manage_media_uploads');
    }
}
