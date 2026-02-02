<?php

declare(strict_types=1);

namespace Modules\PlanManagement\Actions;

use Modules\PaymentGateway\Actions\GenerateInvoiceAction;
use Modules\PaymentGateway\Actions\CreateSubscriptionAction;
use Modules\PaymentGateway\DTOs\CreateInvoiceDTO;
use Modules\PaymentGateway\DTOs\CreateSubscriptionDTO;
use Modules\PaymentGateway\Models\Subscription as PaymentSubscription;
use Modules\PlanManagement\Models\Plan;
use Modules\UserManagement\Models\User;

/**
 * Subscribe a user to a plan.
 * - Recurring (weekly/monthly/yearly): creates gateway subscription + our Subscription record.
 * - Lifetime: creates Invoice + our Subscription (pending); subscription is activated when invoice is paid (InvoicePaid event).
 */
class SubscribeToPlanAction
{
    public function __construct(
        private GenerateInvoiceAction $generateInvoiceAction,
        private CreateSubscriptionAction $createSubscriptionAction,
        private NotifyAdminsNewSubscriptionAction $notifyAdminsNewSubscriptionAction
    ) {
    }

    /**
     * @return array{subscription: PaymentSubscription, invoice?: \Modules\PaymentGateway\Models\Invoice, payment_required?: bool}
     */
    public function execute(User $user, Plan $plan, array $gatewayData = []): array
    {
        if ($plan->isLifetime()) {
            return $this->subscribeLifetime($user, $plan);
        }

        return $this->subscribeRecurring($user, $plan, $gatewayData);
    }

    /**
     * Recurring: create subscription via PaymentGateway (gateway + our record).
     */
    private function subscribeRecurring(User $user, Plan $plan, array $gatewayData): array
    {
        $interval = $plan->getIntervalForGateway();
        if ($interval === null) {
            throw new \InvalidArgumentException('Plan subscription_type must be weekly, monthly, or yearly for recurring.');
        }

        $dto = new CreateSubscriptionDTO(
            userId: $user->id,
            name: $plan->name,
            interval: $interval,
            amount: (float) $plan->price,
            currency: $plan->currency,
            paymentGatewayId: null,
            subscriptionableType: Plan::class,
            subscriptionableId: $plan->id,
            startDate: now(),
            gatewayData: $gatewayData ?: null,
            metadata: ['plan_id' => $plan->id],
        );

        $sub = $this->createSubscriptionAction->execute($dto);
        $this->notifyAdminsNewSubscriptionAction->execute($sub);

        return ['subscription' => $sub];
    }

    /**
     * Lifetime: create invoice + subscription (pending). Subscription is activated on InvoicePaid.
     */
    private function subscribeLifetime(User $user, Plan $plan): array
    {
        $invoiceDto = new CreateInvoiceDTO(
            userId: $user->id,
            type: 'subscription',
            subtotal: (float) $plan->price,
            tax: 0.0,
            discount: 0.0,
            currency: $plan->currency,
            dueDate: now()->addDays(7),
            notes: "Lifetime plan: {$plan->name}",
            metadata: ['plan_id' => $plan->id, 'subscription_type' => 'lifetime'],
            invoiceableType: Plan::class,
            invoiceableId: $plan->id,
        );

        $invoice = $this->generateInvoiceAction->execute($invoiceDto, null);
        $invoice->status = 'pending';
        $invoice->save();

        $sub = new PaymentSubscription();
        $sub->subscription_number = $this->generateSubscriptionNumber();
        $sub->user_id = $user->id;
        $sub->subscriptionable_type = Plan::class;
        $sub->subscriptionable_id = $plan->id;
        $sub->name = $plan->name;
        $sub->interval = 'yearly';
        $sub->amount = (float) $plan->price;
        $sub->currency = $plan->currency;
        $sub->status = 'pending';
        $sub->start_date = now();
        $sub->end_date = null;
        $sub->next_billing_date = null;
        $sub->gateway_subscription_id = null;
        $sub->metadata = ['plan_id' => $plan->id, 'invoice_id' => $invoice->id];
        $sub->save();
        $this->notifyAdminsNewSubscriptionAction->execute($sub);

        return [
            'subscription' => $sub,
            'invoice' => $invoice,
            'payment_required' => true,
        ];
    }

    private function generateSubscriptionNumber(): string
    {
        return 'SUB-' . now()->format('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
    }
}
