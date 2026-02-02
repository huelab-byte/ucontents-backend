<?php

declare(strict_types=1);

namespace Modules\AudioLibrary\Policies;

use Modules\AudioLibrary\Models\Audio;
use Modules\UserManagement\Models\User;

class AudioPolicy
{
    /**
     * Determine if the user can view any audio.
     */
    public function viewAny(User $user): bool
    {
        if ($user->isAdmin()) {
            return $user->hasPermission('view_all_audio');
        }
        return $user->hasPermission('view_audio') || $user->hasPermission('use_audio_library');
    }

    /**
     * Determine if the user can view the audio.
     */
    public function view(User $user, Audio $audio): bool
    {
        if ($user->isAdmin()) {
            return $user->hasPermission('view_all_audio');
        }
        if ($audio->user_id === $user->id && $user->hasPermission('view_audio')) {
            return true;
        }
        // Browse shared: use_audio_library allows viewing ready items
        return $user->hasPermission('use_audio_library') && $audio->status === 'ready';
    }

    /**
     * Determine if the user can create audio.
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('upload_audio');
    }

    /**
     * Determine if the user can update the audio.
     */
    public function update(User $user, Audio $audio): bool
    {
        if ($user->isAdmin()) {
            return $user->hasPermission('delete_any_audio');
        }
        return $audio->user_id === $user->id && $user->hasPermission('manage_audio');
    }

    /**
     * Determine if the user can delete the audio.
     */
    public function delete(User $user, Audio $audio): bool
    {
        if ($user->isAdmin()) {
            return $user->hasPermission('delete_any_audio');
        }
        return $audio->user_id === $user->id && $user->hasPermission('manage_audio');
    }
}
