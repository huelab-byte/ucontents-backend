<?php

declare(strict_types=1);

namespace Modules\Client\Policies;

use Modules\UserManagement\Models\User;
use Modules\Client\Models\ApiKey;

/**
 * Policy for API Key authorization
 */
class ApiKeyPolicy
{
    /**
     * Determine if the user can view any API keys.
     */
    public function viewAny(User $user): bool
    {
        // Only admins can view API keys
        return $user->isAdmin();
    }

    /**
     * Determine if the user can view the API key.
     */
    public function view(User $user, ApiKey $apiKey): bool
    {
        return $this->viewAny($user);
    }

    /**
     * Determine if the user can create API keys.
     */
    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    /**
     * Determine if the user can revoke the API key.
     */
    public function revoke(User $user, ApiKey $apiKey): bool
    {
        return $this->viewAny($user);
    }

    /**
     * Determine if the user can rotate the API key.
     */
    public function rotate(User $user, ApiKey $apiKey): bool
    {
        return $this->viewAny($user);
    }
}
