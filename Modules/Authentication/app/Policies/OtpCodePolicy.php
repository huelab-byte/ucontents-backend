<?php

declare(strict_types=1);

namespace Modules\Authentication\Policies;

use Modules\Authentication\Models\OtpCode;
use Modules\UserManagement\Models\User;

/**
 * Policy for OtpCode authorization
 */
class OtpCodePolicy
{
    /**
     * Determine if the user can view any OTP codes.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('manage_auth_settings');
    }

    /**
     * Determine if the user can view the OTP code.
     */
    public function view(User $user, OtpCode $code): bool
    {
        return $user->id === $code->user_id || $user->can('manage_auth_settings');
    }

    /**
     * Determine if the user can create OTP codes.
     */
    public function create(User $user): bool
    {
        // Any authenticated user can request an OTP
        return true;
    }

    /**
     * Determine if the user can delete the OTP code.
     */
    public function delete(User $user, OtpCode $code): bool
    {
        return $user->id === $code->user_id || $user->can('manage_auth_settings');
    }
}
