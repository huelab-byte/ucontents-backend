<?php

declare(strict_types=1);

namespace Modules\CustomerManagement\Policies;

use Modules\UserManagement\Models\User;

class CustomerPolicy
{
    /**
     * Determine whether the user can view any customers (list).
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('view_customers') || $user->hasPermission('manage_customers');
    }

    /**
     * Determine whether the user can view the customer profile.
     * Target user must have customer role (enforced in action).
     */
    public function view(User $user, User $customer): bool
    {
        if (! $customer->hasRole('customer')) {
            return false;
        }

        return $user->hasPermission('view_customers') || $user->hasPermission('manage_customers');
    }
}
