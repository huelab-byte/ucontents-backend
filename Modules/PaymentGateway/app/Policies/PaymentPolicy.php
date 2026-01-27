<?php

declare(strict_types=1);

namespace Modules\PaymentGateway\Policies;

use Modules\PaymentGateway\Models\Payment;
use Modules\UserManagement\Models\User;

class PaymentPolicy
{
    /**
     * Determine if the user can view any payments.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('view_all_payments') || $user->hasPermission('make_payments');
    }

    /**
     * Determine if the user can view the payment.
     */
    public function view(User $user, Payment $payment): bool
    {
        // Admins can view all payments
        if ($user->hasPermission('view_all_payments')) {
            return true;
        }

        // Users can view their own payments
        return $user->hasPermission('make_payments') && $payment->user_id === $user->id;
    }

    /**
     * Determine if the user can create payments.
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('make_payments');
    }
}
