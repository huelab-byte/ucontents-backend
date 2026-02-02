<?php

declare(strict_types=1);

namespace Modules\CustomerManagement\Actions;

use Modules\CustomerManagement\DTOs\CustomerProfileData;
use Modules\PaymentGateway\Models\Invoice;
use Modules\PaymentGateway\Models\Payment;
use Modules\PaymentGateway\Models\Subscription;
use Modules\UserManagement\Models\User;

class GetCustomerProfileAction
{
    /**
     * Get customer profile with aggregates. User must have customer role.
     */
    public function execute(User $user): CustomerProfileData
    {
        if (! $user->hasRole('customer')) {
            abort(404, 'Customer not found.');
        }

        $user->load(['roles']);

        $userId = $user->id;

        $invoicesCount = Invoice::where('user_id', $userId)->count();
        $paymentsCount = Payment::where('user_id', $userId)->count();
        $activeSubscriptions = Subscription::where('user_id', $userId)
            ->where('status', 'active')
            ->with('subscriptionable')
            ->get();

        $lastInvoices = Invoice::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        $lastPayments = Payment::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        $supportTicketsCount = 0;
        if (class_exists(\Modules\Support\Models\SupportTicket::class)) {
            $supportTicketsCount = \Modules\Support\Models\SupportTicket::where('user_id', $userId)->count();
        }

        return new CustomerProfileData(
            user: $user,
            invoicesCount: $invoicesCount,
            paymentsCount: $paymentsCount,
            activeSubscriptions: $activeSubscriptions,
            lastInvoices: $lastInvoices,
            lastPayments: $lastPayments,
            supportTicketsCount: $supportTicketsCount
        );
    }
}
