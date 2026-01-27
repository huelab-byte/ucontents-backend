<?php

declare(strict_types=1);

namespace Modules\Authentication\Policies;

use Modules\Authentication\Models\MagicLinkToken;
use Modules\UserManagement\Models\User;

/**
 * Policy for MagicLinkToken authorization
 */
class MagicLinkTokenPolicy
{
    /**
     * Determine if the user can view any magic link tokens.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('manage_auth_settings');
    }

    /**
     * Determine if the user can view the magic link token.
     */
    public function view(User $user, MagicLinkToken $token): bool
    {
        // Users can view their own tokens, admins can view all
        return $user->id === $token->user_id || $user->can('manage_auth_settings');
    }

    /**
     * Determine if the user can create magic link tokens.
     */
    public function create(User $user): bool
    {
        // Any authenticated user can request a magic link
        return true;
    }

    /**
     * Determine if the user can delete the magic link token.
     */
    public function delete(User $user, MagicLinkToken $token): bool
    {
        return $user->id === $token->user_id || $user->can('manage_auth_settings');
    }
}
