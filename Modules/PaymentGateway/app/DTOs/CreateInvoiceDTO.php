<?php

declare(strict_types=1);

namespace Modules\PaymentGateway\DTOs;

/**
 * Data Transfer Object for creating an invoice
 */
readonly class CreateInvoiceDTO
{
    public function __construct(
        public int $userId,
        public string $type, // package, subscription, one_time
        public float $subtotal,
        public float $tax = 0.0,
        public float $discount = 0.0,
        public string $currency = 'USD',
        public ?\DateTimeInterface $dueDate = null,
        public ?string $notes = null,
        public ?array $metadata = null,
        public ?string $invoiceableType = null,
        public ?int $invoiceableId = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            userId: $data['user_id'],
            type: $data['type'],
            subtotal: (float) $data['subtotal'],
            tax: isset($data['tax']) ? (float) $data['tax'] : 0.0,
            discount: isset($data['discount']) ? (float) $data['discount'] : 0.0,
            currency: $data['currency'] ?? 'USD',
            dueDate: isset($data['due_date']) ? new \DateTime($data['due_date']) : null,
            notes: $data['notes'] ?? null,
            metadata: $data['metadata'] ?? null,
            invoiceableType: $data['invoiceable_type'] ?? null,
            invoiceableId: isset($data['invoiceable_id']) ? (int) $data['invoiceable_id'] : null,
        );
    }
}
