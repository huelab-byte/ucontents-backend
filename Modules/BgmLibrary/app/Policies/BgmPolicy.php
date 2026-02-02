<?php

declare(strict_types=1);

namespace Modules\BgmLibrary\Policies;

use Modules\BgmLibrary\Models\Bgm;
use Modules\UserManagement\Models\User;

class BgmPolicy
{
    /**
     * Determine if the user can view any BGM.
     */
    public function viewAny(User $user): bool
    {
        if ($user->isAdmin()) {
            return $user->hasPermission('view_all_bgm');
        }
        return $user->hasPermission('view_bgm') || $user->hasPermission('use_bgm_library');
    }

    /**
     * Determine if the user can view the BGM.
     */
    public function view(User $user, Bgm $bgm): bool
    {
        if ($user->isAdmin()) {
            return $user->hasPermission('view_all_bgm');
        }
        if ($bgm->user_id === $user->id && $user->hasPermission('view_bgm')) {
            return true;
        }
        return $user->hasPermission('use_bgm_library') && $bgm->status === 'ready';
    }

    /**
     * Determine if the user can create BGM.
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('upload_bgm');
    }

    /**
     * Determine if the user can update the BGM.
     */
    public function update(User $user, Bgm $bgm): bool
    {
        if ($user->isAdmin()) {
            return $user->hasPermission('delete_any_bgm');
        }
        return $bgm->user_id === $user->id && $user->hasPermission('manage_bgm');
    }

    /**
     * Determine if the user can delete the BGM.
     */
    public function delete(User $user, Bgm $bgm): bool
    {
        if ($user->isAdmin()) {
            return $user->hasPermission('delete_any_bgm');
        }
        return $bgm->user_id === $user->id && $user->hasPermission('manage_bgm');
    }
}
