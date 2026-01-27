<?php

declare(strict_types=1);

namespace Modules\Authentication\Policies;

use Modules\UserManagement\Models\User;

/**
 * Policy for Authentication Settings authorization
 */
class AuthSettingsPolicy
{
    /**
     * Determine if the user can view settings.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('manage_auth_settings');
    }

    /**
     * Determine if the user can update settings.
     */
    public function update(User $user): bool
    {
        return $user->can('manage_auth_settings');
    }
}
