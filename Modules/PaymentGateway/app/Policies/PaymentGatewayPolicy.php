<?php

declare(strict_types=1);

namespace Modules\PaymentGateway\Policies;

use Modules\PaymentGateway\Models\PaymentGateway;
use Modules\UserManagement\Models\User;

class PaymentGatewayPolicy
{
    /**
     * Determine if the user can view any payment gateways.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('manage_payment_gateways');
    }

    /**
     * Determine if the user can view the payment gateway.
     */
    public function view(User $user, PaymentGateway $paymentGateway): bool
    {
        return $user->hasPermission('manage_payment_gateways');
    }

    /**
     * Determine if the user can create payment gateways.
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('manage_payment_gateways');
    }

    /**
     * Determine if the user can update the payment gateway.
     */
    public function update(User $user, PaymentGateway $paymentGateway): bool
    {
        return $user->hasPermission('manage_payment_gateways');
    }

    /**
     * Determine if the user can delete the payment gateway.
     */
    public function delete(User $user, PaymentGateway $paymentGateway): bool
    {
        return $user->hasPermission('manage_payment_gateways');
    }
}
