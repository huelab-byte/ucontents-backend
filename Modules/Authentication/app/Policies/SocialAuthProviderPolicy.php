<?php

declare(strict_types=1);

namespace Modules\Authentication\Policies;

use Modules\Authentication\Models\SocialAuthProvider;
use Modules\UserManagement\Models\User;

/**
 * Policy for SocialAuthProvider authorization
 */
class SocialAuthProviderPolicy
{
    /**
     * Determine if the user can view any social auth providers.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('manage_auth_settings');
    }

    /**
     * Determine if the user can view the social auth provider.
     */
    public function view(User $user, SocialAuthProvider $provider): bool
    {
        // Users can view their own linked providers
        return $user->id === $provider->user_id || $user->can('manage_auth_settings');
    }

    /**
     * Determine if the user can create social auth providers.
     */
    public function create(User $user): bool
    {
        // Any authenticated user can link a social auth provider
        return true;
    }

    /**
     * Determine if the user can update the social auth provider.
     */
    public function update(User $user, SocialAuthProvider $provider): bool
    {
        return $user->id === $provider->user_id || $user->can('manage_auth_settings');
    }

    /**
     * Determine if the user can delete the social auth provider.
     */
    public function delete(User $user, SocialAuthProvider $provider): bool
    {
        return $user->id === $provider->user_id || $user->can('manage_auth_settings');
    }
}
