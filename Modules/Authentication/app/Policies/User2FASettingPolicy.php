<?php

declare(strict_types=1);

namespace Modules\Authentication\Policies;

use Modules\Authentication\Models\User2FASetting;
use Modules\UserManagement\Models\User;

/**
 * Policy for User2FASetting authorization
 */
class User2FASettingPolicy
{
    /**
     * Determine if the user can view any 2FA settings.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('manage_auth_settings');
    }

    /**
     * Determine if the user can view the 2FA setting.
     */
    public function view(User $user, User2FASetting $setting): bool
    {
        return $user->id === $setting->user_id || $user->can('manage_auth_settings');
    }

    /**
     * Determine if the user can create 2FA settings.
     */
    public function create(User $user): bool
    {
        // Any authenticated user can set up 2FA
        return true;
    }

    /**
     * Determine if the user can update the 2FA setting.
     */
    public function update(User $user, User2FASetting $setting): bool
    {
        return $user->id === $setting->user_id || $user->can('manage_auth_settings');
    }

    /**
     * Determine if the user can delete the 2FA setting.
     */
    public function delete(User $user, User2FASetting $setting): bool
    {
        return $user->id === $setting->user_id || $user->can('manage_auth_settings');
    }
}
