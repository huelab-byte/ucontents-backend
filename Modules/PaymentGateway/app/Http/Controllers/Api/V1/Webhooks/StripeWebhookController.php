<?php

declare(strict_types=1);

namespace Modules\PaymentGateway\Http\Controllers\Api\V1\Webhooks;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Modules\Core\Http\Controllers\Api\BaseApiController;
use Modules\PaymentGateway\Models\Payment;
use Modules\PaymentGateway\Models\PaymentGateway;
use Modules\PaymentGateway\Models\Subscription;
use Modules\PaymentGateway\Services\PaymentGatewayService;
use Stripe\Event;
use Stripe\Stripe;

/**
 * Stripe Webhook Controller
 * 
 * Handles incoming webhook events from Stripe
 */
class StripeWebhookController extends BaseApiController
{
    public function __construct(
        private PaymentGatewayService $gatewayService
    ) {
    }

    /**
     * Handle Stripe webhook
     */
    public function handle(Request $request): JsonResponse
    {
        $payload = $request->getContent();
        $signature = $request->header('Stripe-Signature');

        if (!$signature) {
            return $this->error('Missing Stripe signature', 400);
        }

        // Get Stripe gateway
        $gateway = PaymentGateway::where('name', 'stripe')
            ->where('is_active', true)
            ->first();

        if (!$gateway) {
            Log::warning('Stripe webhook received but gateway not configured');
            return $this->error('Gateway not configured', 400);
        }

        // Verify webhook signature
        if (!$this->gatewayService->verifyWebhook($gateway, $payload, $signature)) {
            Log::warning('Stripe webhook signature verification failed');
            return $this->error('Invalid signature', 400);
        }

        try {
            $event = \json_decode($payload, true);

            if (!isset($event['type'])) {
                return $this->error('Invalid event data', 400);
            }

            // Handle different event types
            match ($event['type']) {
                'payment_intent.succeeded' => $this->handlePaymentSucceeded($event),
                'payment_intent.payment_failed' => $this->handlePaymentFailed($event),
                'charge.refunded' => $this->handleRefunded($event),
                'customer.subscription.created',
                'customer.subscription.updated' => $this->handleSubscriptionUpdated($event),
                'customer.subscription.deleted' => $this->handleSubscriptionDeleted($event),
                default => Log::info('Unhandled Stripe webhook event', ['type' => $event['type']]),
            };

            return $this->success(['received' => true], 'Webhook processed successfully');
        } catch (\Exception $e) {
            Log::error('Stripe webhook processing error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->error('Webhook processing failed', 500);
        }
    }

    /**
     * Handle payment succeeded event
     */
    private function handlePaymentSucceeded(array $event): void
    {
        $paymentIntent = $event['data']['object'];
        $paymentIntentId = $paymentIntent['id'];

        $payment = Payment::where('gateway_transaction_id', $paymentIntentId)->first();

        if ($payment) {
            $payment->status = 'completed';
            $payment->processed_at = now();
            $payment->gateway_response = array_merge($payment->gateway_response ?? [], [
                'webhook_event' => $event,
            ]);
            $payment->save();

            // Update invoice
            if ($payment->invoice) {
                $payment->invoice->status = 'paid';
                $payment->invoice->paid_at = now();
                $payment->invoice->save();
                \Modules\PaymentGateway\Events\InvoicePaid::dispatch($payment->invoice);
            }
        }
    }

    /**
     * Handle payment failed event
     */
    private function handlePaymentFailed(array $event): void
    {
        $paymentIntent = $event['data']['object'];
        $paymentIntentId = $paymentIntent['id'];

        $payment = Payment::where('gateway_transaction_id', $paymentIntentId)->first();

        if ($payment) {
            $payment->status = 'failed';
            $payment->failure_reason = $paymentIntent['last_payment_error']['message'] ?? 'Payment failed';
            $payment->gateway_response = array_merge($payment->gateway_response ?? [], [
                'webhook_event' => $event,
            ]);
            $payment->save();
        }
    }

    /**
     * Handle refunded event
     */
    private function handleRefunded(array $event): void
    {
        $charge = $event['data']['object'];
        $paymentIntentId = $charge['payment_intent'] ?? null;

        if ($paymentIntentId) {
            $payment = Payment::where('gateway_transaction_id', $paymentIntentId)->first();

            if ($payment) {
                // Update payment status if fully refunded
                $refunds = $charge['refunds']['data'] ?? [];
                $totalRefunded = array_sum(array_column($refunds, 'amount')) / 100; // Convert from cents

                if ($totalRefunded >= $payment->amount) {
                    $payment->status = 'refunded';
                    $payment->save();
                }

                // Update invoice if fully refunded
                if ($payment->invoice) {
                    $totalInvoiceRefunded = $payment->invoice->refunds()
                        ->where('status', 'completed')
                        ->sum('amount');

                    if ($totalInvoiceRefunded >= $payment->invoice->total) {
                        $payment->invoice->status = 'refunded';
                        $payment->invoice->save();
                    }
                }
            }
        }
    }

    /**
     * Handle subscription updated event
     */
    private function handleSubscriptionUpdated(array $event): void
    {
        $stripeSubscription = $event['data']['object'];
        $subscriptionId = $stripeSubscription['id'];

        $subscription = Subscription::where('gateway_subscription_id', $subscriptionId)->first();

        if ($subscription) {
            $subscription->status = match ($stripeSubscription['status']) {
                'active', 'trialing' => 'active',
                'past_due', 'unpaid' => 'pending',
                'canceled', 'incomplete_expired' => 'cancelled',
                default => 'pending',
            };

            // Update next billing date
            if (isset($stripeSubscription['current_period_end'])) {
                $subscription->next_billing_date = \Carbon\Carbon::createFromTimestamp(
                    $stripeSubscription['current_period_end']
                );
            }

            $subscription->gateway_data = array_merge($subscription->gateway_data ?? [], [
                'webhook_event' => $event,
            ]);
            $subscription->save();
        }
    }

    /**
     * Handle subscription deleted event
     */
    private function handleSubscriptionDeleted(array $event): void
    {
        $stripeSubscription = $event['data']['object'];
        $subscriptionId = $stripeSubscription['id'];

        $subscription = Subscription::where('gateway_subscription_id', $subscriptionId)->first();

        if ($subscription) {
            $subscription->status = 'cancelled';
            $subscription->end_date = now();
            $subscription->gateway_data = array_merge($subscription->gateway_data ?? [], [
                'webhook_event' => $event,
            ]);
            $subscription->save();
        }
    }
}
