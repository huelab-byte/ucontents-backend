<?php

declare(strict_types=1);

namespace Modules\PaymentGateway\DTOs;

/**
 * Data Transfer Object for creating a subscription
 */
readonly class CreateSubscriptionDTO
{
    public function __construct(
        public int $userId,
        public string $name,
        public string $interval, // weekly, monthly, yearly
        public float $amount,
        public string $currency = 'USD',
        public ?int $paymentGatewayId = null,
        public ?string $subscriptionableType = null,
        public ?int $subscriptionableId = null,
        public ?\DateTimeInterface $startDate = null,
        public ?array $gatewayData = null,
        public ?array $metadata = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            userId: $data['user_id'],
            name: $data['name'],
            interval: $data['interval'],
            amount: (float) $data['amount'],
            currency: $data['currency'] ?? 'USD',
            paymentGatewayId: isset($data['payment_gateway_id']) ? (int) $data['payment_gateway_id'] : null,
            subscriptionableType: $data['subscriptionable_type'] ?? null,
            subscriptionableId: isset($data['subscriptionable_id']) ? (int) $data['subscriptionable_id'] : null,
            startDate: isset($data['start_date']) ? new \DateTime($data['start_date']) : null,
            gatewayData: $data['gateway_data'] ?? null,
            metadata: $data['metadata'] ?? null,
        );
    }
}
