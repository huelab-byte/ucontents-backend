<?php

declare(strict_types=1);

namespace Modules\PaymentGateway\Services\Gateways;

use Modules\PaymentGateway\Exceptions\PaymentProcessingException;
use Modules\PaymentGateway\Exceptions\RefundProcessingException;
use Modules\PaymentGateway\Exceptions\SubscriptionProcessingException;
use Stripe\Exception\ApiErrorException;
use Stripe\Stripe;
use Stripe\StripeClient;

/**
 * Stripe payment gateway service
 */
class StripeService implements GatewayInterface
{
    private StripeClient $client;

    public function __construct(string $apiKey)
    {
        Stripe::setApiKey($apiKey);
        $this->client = new StripeClient($apiKey);
    }

    /**
     * Process a payment
     */
    public function processPayment(array $paymentData): array
    {
        try {
            $amount = (int) ($paymentData['amount'] * 100); // Convert to cents
            $currency = strtolower($paymentData['currency'] ?? 'usd');
            $paymentMethodId = $paymentData['payment_method_id'] ?? null;
            $customerId = $paymentData['customer_id'] ?? null;

            if (!$paymentMethodId) {
                throw new PaymentProcessingException('Payment method ID is required for Stripe');
            }

            // Create or retrieve customer
            if (!$customerId) {
                $customer = $this->client->customers->create([
                    'email' => $paymentData['customer_email'] ?? null,
                    'metadata' => $paymentData['metadata'] ?? [],
                ]);
                $customerId = $customer->id;
            }

            // Create payment intent
            $paymentIntent = $this->client->paymentIntents->create([
                'amount' => $amount,
                'currency' => $currency,
                'customer' => $customerId,
                'payment_method' => $paymentMethodId,
                'confirmation_method' => 'manual',
                'confirm' => true,
                'metadata' => array_merge($paymentData['metadata'] ?? [], [
                    'invoice_id' => $paymentData['invoice_id'] ?? null,
                ]),
            ]);

            return [
                'transaction_id' => $paymentIntent->id,
                'status' => $this->mapStripeStatus($paymentIntent->status),
                'customer_id' => $customerId,
                'response' => [
                    'payment_intent' => $paymentIntent->toArray(),
                ],
            ];
        } catch (ApiErrorException $e) {
            throw new PaymentProcessingException(
                'Stripe payment failed: ' . $e->getMessage(),
                $e->getCode(),
                $e,
                ['stripe_error' => $e->getJsonBody()]
            );
        }
    }

    /**
     * Create a subscription
     */
    public function createSubscription(array $subscriptionData): array
    {
        try {
            $amount = (int) ($subscriptionData['amount'] * 100);
            $currency = strtolower($subscriptionData['currency'] ?? 'usd');
            $customerId = $subscriptionData['customer_id'] ?? null;
            $paymentMethodId = $subscriptionData['payment_method_id'] ?? null;

            if (!$customerId || !$paymentMethodId) {
                throw new SubscriptionProcessingException('Customer ID and Payment Method ID are required for Stripe subscriptions');
            }

            // Create price based on interval
            $interval = $this->mapInterval($subscriptionData['interval'] ?? 'monthly');
            
            $price = $this->client->prices->create([
                'unit_amount' => $amount,
                'currency' => $currency,
                'recurring' => ['interval' => $interval],
                'product_data' => [
                    'name' => $subscriptionData['name'] ?? 'Subscription',
                ],
            ]);

            // Create subscription
            $subscription = $this->client->subscriptions->create([
                'customer' => $customerId,
                'items' => [['price' => $price->id]],
                'default_payment_method' => $paymentMethodId,
                'metadata' => $subscriptionData['metadata'] ?? [],
            ]);

            return [
                'subscription_id' => $subscription->id,
                'status' => $this->mapStripeSubscriptionStatus($subscription->status),
                'response' => [
                    'subscription' => $subscription->toArray(),
                ],
            ];
        } catch (ApiErrorException $e) {
            throw new SubscriptionProcessingException(
                'Stripe subscription creation failed: ' . $e->getMessage(),
                $e->getCode(),
                $e,
                ['stripe_error' => $e->getJsonBody()]
            );
        }
    }

    /**
     * Process a refund
     */
    public function processRefund(array $refundData): array
    {
        try {
            $paymentIntentId = $refundData['payment_intent_id'] ?? null;
            $amount = isset($refundData['amount']) ? (int) ($refundData['amount'] * 100) : null;

            if (!$paymentIntentId) {
                throw new RefundProcessingException('Payment Intent ID is required for Stripe refunds');
            }

            $refundParams = [
                'payment_intent' => $paymentIntentId,
            ];

            if ($amount !== null) {
                $refundParams['amount'] = $amount;
            }

            if (isset($refundData['reason'])) {
                $refundParams['reason'] = $refundData['reason'];
            }

            $refund = $this->client->refunds->create($refundParams);

            return [
                'refund_id' => $refund->id,
                'status' => $this->mapStripeRefundStatus($refund->status),
                'response' => [
                    'refund' => $refund->toArray(),
                ],
            ];
        } catch (ApiErrorException $e) {
            throw new RefundProcessingException(
                'Stripe refund failed: ' . $e->getMessage(),
                $e->getCode(),
                $e,
                ['stripe_error' => $e->getJsonBody()]
            );
        }
    }

    /**
     * Cancel a subscription
     */
    public function cancelSubscription(string $subscriptionId): bool
    {
        try {
            $this->client->subscriptions->cancel($subscriptionId);
            return true;
        } catch (ApiErrorException $e) {
            throw new SubscriptionProcessingException(
                'Stripe subscription cancellation failed: ' . $e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * Verify webhook signature
     */
    public function verifyWebhook(string $payload, string $signature): bool
    {
        try {
            $endpointSecret = config('paymentgateway.stripe.webhook_secret');
            
            if (!$endpointSecret) {
                return false;
            }

            \Stripe\Webhook::constructEvent($payload, $signature, $endpointSecret);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Map Stripe payment status to our status
     */
    private function mapStripeStatus(string $stripeStatus): string
    {
        return match ($stripeStatus) {
            'succeeded' => 'completed',
            'processing', 'requires_confirmation', 'requires_action' => 'processing',
            'requires_payment_method', 'requires_capture' => 'pending',
            'canceled' => 'cancelled',
            default => 'failed',
        };
    }

    /**
     * Map Stripe subscription status
     */
    private function mapStripeSubscriptionStatus(string $stripeStatus): string
    {
        return match ($stripeStatus) {
            'active', 'trialing' => 'active',
            'past_due', 'unpaid' => 'pending',
            'canceled', 'incomplete_expired' => 'cancelled',
            default => 'pending',
        };
    }

    /**
     * Map Stripe refund status
     */
    private function mapStripeRefundStatus(string $stripeStatus): string
    {
        return match ($stripeStatus) {
            'succeeded' => 'completed',
            'pending' => 'processing',
            'failed', 'canceled' => 'failed',
            default => 'pending',
        };
    }

    /**
     * Map our interval to Stripe interval
     */
    private function mapInterval(string $interval): string
    {
        return match ($interval) {
            'weekly' => 'week',
            'monthly' => 'month',
            'yearly' => 'year',
            default => 'month',
        };
    }
}
