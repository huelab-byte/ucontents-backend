<?php

declare(strict_types=1);

namespace Modules\PaymentGateway\Policies;

use Modules\PaymentGateway\Models\Refund;
use Modules\UserManagement\Models\User;

class RefundPolicy
{
    /**
     * Determine if the user can view any refunds.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('process_refunds') || $user->hasPermission('request_refunds');
    }

    /**
     * Determine if the user can view the refund.
     */
    public function view(User $user, Refund $refund): bool
    {
        // Admins can view all refunds
        if ($user->hasPermission('process_refunds')) {
            return true;
        }

        // Users can view their own refunds
        return $user->hasPermission('request_refunds') && $refund->user_id === $user->id;
    }

    /**
     * Determine if the user can create refunds.
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('process_refunds') || $user->hasPermission('request_refunds');
    }
}
