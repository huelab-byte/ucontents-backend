<?php

declare(strict_types=1);

namespace Modules\PlanManagement\Listeners;

use Modules\PaymentGateway\Events\InvoicePaid;
use Modules\PaymentGateway\Models\Subscription;

class ActivateLifetimeSubscriptionOnInvoicePaid
{
    public function handle(InvoicePaid $event): void
    {
        $invoice = $event->invoice;

        if ($invoice->invoiceable_type !== \Modules\PlanManagement\Models\Plan::class) {
            return;
        }

        $planId = $invoice->invoiceable_id;
        $userId = $invoice->user_id;

        $subscription = Subscription::where('user_id', $userId)
            ->where('subscriptionable_type', \Modules\PlanManagement\Models\Plan::class)
            ->where('subscriptionable_id', $planId)
            ->where('status', 'pending')
            ->whereNull('next_billing_date')
            ->first();

        if ($subscription) {
            $subscription->status = 'active';
            $subscription->save();
        }
    }
}
