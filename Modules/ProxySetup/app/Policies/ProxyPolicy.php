<?php

declare(strict_types=1);

namespace Modules\ProxySetup\Policies;

use Modules\ProxySetup\Models\Proxy;
use Modules\UserManagement\Models\User;

class ProxyPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('view_proxies') || $user->hasPermission('manage_proxies');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Proxy $proxy): bool
    {
        if (!$user->hasPermission('view_proxies') && !$user->hasPermission('manage_proxies')) {
            return false;
        }

        return $proxy->user_id === $user->id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('manage_proxies');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Proxy $proxy): bool
    {
        if (!$user->hasPermission('manage_proxies')) {
            return false;
        }

        return $proxy->user_id === $user->id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Proxy $proxy): bool
    {
        if (!$user->hasPermission('manage_proxies')) {
            return false;
        }

        return $proxy->user_id === $user->id;
    }
}
