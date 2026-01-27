<?php

declare(strict_types=1);

namespace Modules\PaymentGateway\DTOs;

/**
 * Data Transfer Object for processing a payment
 */
readonly class ProcessPaymentDTO
{
    public function __construct(
        public int $invoiceId,
        public int $userId,
        public ?int $paymentGatewayId = null,
        public string $gatewayName = 'stripe',
        public string $paymentMethod = 'card',
        public ?array $gatewayData = null, // Card details, payment token, etc.
        public ?array $metadata = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            invoiceId: $data['invoice_id'],
            userId: $data['user_id'],
            paymentGatewayId: isset($data['payment_gateway_id']) ? (int) $data['payment_gateway_id'] : null,
            gatewayName: $data['gateway_name'] ?? 'stripe',
            paymentMethod: $data['payment_method'] ?? 'card',
            gatewayData: $data['gateway_data'] ?? null,
            metadata: $data['metadata'] ?? null,
        );
    }
}
