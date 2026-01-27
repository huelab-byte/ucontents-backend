<?php

declare(strict_types=1);

namespace Modules\PaymentGateway\Policies;

use Modules\PaymentGateway\Models\Subscription;
use Modules\UserManagement\Models\User;

class SubscriptionPolicy
{
    /**
     * Determine if the user can view any subscriptions.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('view_all_subscriptions') || $user->hasPermission('manage_own_subscriptions');
    }

    /**
     * Determine if the user can view the subscription.
     */
    public function view(User $user, Subscription $subscription): bool
    {
        // Admins can view all subscriptions
        if ($user->hasPermission('view_all_subscriptions')) {
            return true;
        }

        // Users can view their own subscriptions
        return $user->hasPermission('manage_own_subscriptions') && $subscription->user_id === $user->id;
    }

    /**
     * Determine if the user can create subscriptions.
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('manage_own_subscriptions');
    }
}
