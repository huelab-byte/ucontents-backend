<?php

declare(strict_types=1);

namespace Modules\Client\Policies;

use Modules\UserManagement\Models\User;
use Modules\Client\Models\ApiClient;

/**
 * Policy for API Client authorization
 */
class ApiClientPolicy
{
    /**
     * Determine if the user can view any API clients.
     */
    public function viewAny(User $user): bool
    {
        // Only admins can view API clients
        return $user->isAdmin();
    }

    /**
     * Determine if the user can view the API client.
     */
    public function view(User $user, ApiClient $apiClient): bool
    {
        return $this->viewAny($user);
    }

    /**
     * Determine if the user can create API clients.
     */
    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    /**
     * Determine if the user can update the API client.
     */
    public function update(User $user, ApiClient $apiClient): bool
    {
        return $this->viewAny($user);
    }

    /**
     * Determine if the user can delete the API client.
     */
    public function delete(User $user, ApiClient $apiClient): bool
    {
        return $this->viewAny($user);
    }
}
