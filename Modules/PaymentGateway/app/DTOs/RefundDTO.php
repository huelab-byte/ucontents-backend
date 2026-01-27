<?php

declare(strict_types=1);

namespace Modules\PaymentGateway\DTOs;

/**
 * Data Transfer Object for processing a refund
 */
readonly class RefundDTO
{
    public function __construct(
        public int $paymentId,
        public int $userId,
        public float $amount,
        public ?string $reason = null,
        public ?int $paymentGatewayId = null,
        public ?array $metadata = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            paymentId: $data['payment_id'],
            userId: $data['user_id'],
            amount: (float) $data['amount'],
            reason: $data['reason'] ?? null,
            paymentGatewayId: isset($data['payment_gateway_id']) ? (int) $data['payment_gateway_id'] : null,
            metadata: $data['metadata'] ?? null,
        );
    }
}
