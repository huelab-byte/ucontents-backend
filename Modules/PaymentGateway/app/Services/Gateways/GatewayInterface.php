<?php

declare(strict_types=1);

namespace Modules\PaymentGateway\Services\Gateways;

/**
 * Interface for payment gateway implementations
 */
interface GatewayInterface
{
    /**
     * Process a payment
     */
    public function processPayment(array $paymentData): array;

    /**
     * Create a subscription
     */
    public function createSubscription(array $subscriptionData): array;

    /**
     * Process a refund
     */
    public function processRefund(array $refundData): array;

    /**
     * Cancel a subscription
     */
    public function cancelSubscription(string $subscriptionId): bool;

    /**
     * Verify webhook signature
     */
    public function verifyWebhook(string $payload, string $signature): bool;
}
