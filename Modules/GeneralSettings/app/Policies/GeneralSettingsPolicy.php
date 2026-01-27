<?php

declare(strict_types=1);

namespace Modules\GeneralSettings\Policies;

use Modules\UserManagement\Models\User;

/**
 * Policy for General Settings authorization
 */
class GeneralSettingsPolicy
{
    /**
     * Determine if the user can view settings.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('manage_general_settings');
    }

    /**
     * Determine if the user can update settings.
     */
    public function update(User $user): bool
    {
        return $user->can('manage_general_settings');
    }
}
