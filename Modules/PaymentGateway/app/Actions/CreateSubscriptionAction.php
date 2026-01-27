<?php

declare(strict_types=1);

namespace Modules\PaymentGateway\Actions;

use Modules\PaymentGateway\DTOs\CreateSubscriptionDTO;
use Modules\PaymentGateway\Models\PaymentGateway;
use Modules\PaymentGateway\Models\Subscription;
use Modules\PaymentGateway\Services\PaymentGatewayService;

/**
 * Action to create a subscription
 */
class CreateSubscriptionAction
{
    public function __construct(
        private PaymentGatewayService $gatewayService
    ) {
    }

    public function execute(CreateSubscriptionDTO $dto): Subscription
    {
        // Get payment gateway
        $gateway = null;
        if ($dto->paymentGatewayId) {
            $gateway = PaymentGateway::findOrFail($dto->paymentGatewayId);
        } else {
            $gateway = PaymentGateway::where('is_active', true)->firstOrFail();
        }

        // Calculate dates
        $startDate = $dto->startDate ?: now();
        $nextBillingDate = $this->calculateNextBillingDate($startDate, $dto->interval);

        // Prepare subscription data
        $subscriptionData = [
            'amount' => $dto->amount,
            'currency' => $dto->currency,
            'interval' => $dto->interval,
            'name' => $dto->name,
        ];

        // Add gateway-specific data
        if ($dto->gatewayData) {
            $subscriptionData = array_merge($subscriptionData, $dto->gatewayData);
        }

        try {
            // Create subscription in gateway
            $gatewayResponse = $this->gatewayService->createSubscription($gateway, $subscriptionData);

            // Create subscription record
            $subscription = new Subscription();
            $subscription->subscription_number = $this->generateSubscriptionNumber();
            $subscription->user_id = $dto->userId;
            $subscription->name = $dto->name;
            $subscription->interval = $dto->interval;
            $subscription->amount = $dto->amount;
            $subscription->currency = $dto->currency;
            
            // Map gateway status
            $gatewayStatus = $gatewayResponse['status'] ?? 'pending';
            $subscription->status = match ($gatewayStatus) {
                'active' => 'active',
                'pending' => 'pending',
                default => 'pending',
            };
            
            $subscription->start_date = $startDate;
            $subscription->next_billing_date = $nextBillingDate;
            $subscription->payment_gateway_id = $gateway->id;
            $subscription->gateway_subscription_id = $gatewayResponse['subscription_id'] ?? null;
            $subscription->gateway_data = $gatewayResponse;
            $subscription->metadata = $dto->metadata;

            if ($dto->subscriptionableType && $dto->subscriptionableId) {
                $subscription->subscriptionable_type = $dto->subscriptionableType;
                $subscription->subscriptionable_id = $dto->subscriptionableId;
            }

            $subscription->save();

            return $subscription;
        } catch (\Modules\PaymentGateway\Exceptions\SubscriptionProcessingException $e) {
            \Log::error('Subscription creation failed', [
                'user_id' => $dto->userId,
                'gateway' => $gateway->name,
                'error' => $e->getMessage(),
                'gateway_response' => $e->getGatewayResponse(),
            ]);
            throw $e;
        } catch (\Exception $e) {
            \Log::error('Unexpected subscription creation error', [
                'user_id' => $dto->userId,
                'gateway' => $gateway->name,
                'error' => $e->getMessage(),
            ]);
            throw new \Modules\PaymentGateway\Exceptions\SubscriptionProcessingException(
                'Subscription creation failed: ' . $e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * Calculate next billing date based on interval
     */
    private function calculateNextBillingDate(\DateTimeInterface $startDate, string $interval): \DateTime
    {
        $date = \DateTime::createFromInterface($startDate);

        match ($interval) {
            'weekly' => $date->modify('+1 week'),
            'monthly' => $date->modify('+1 month'),
            'yearly' => $date->modify('+1 year'),
            default => $date->modify('+1 month'),
        };

        return $date;
    }

    /**
     * Generate a unique subscription number
     */
    private function generateSubscriptionNumber(): string
    {
        $prefix = 'SUB-';
        $date = now()->format('Ymd');
        $random = strtoupper(substr(uniqid(), -6));

        return $prefix . $date . '-' . $random;
    }
}
