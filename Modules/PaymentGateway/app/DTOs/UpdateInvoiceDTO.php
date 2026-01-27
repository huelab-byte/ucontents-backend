<?php

declare(strict_types=1);

namespace Modules\PaymentGateway\DTOs;

/**
 * Data Transfer Object for updating an invoice
 */
readonly class UpdateInvoiceDTO
{
    public function __construct(
        public ?float $subtotal = null,
        public ?float $tax = null,
        public ?float $discount = null,
        public ?string $status = null,
        public ?\DateTimeInterface $dueDate = null,
        public ?string $notes = null,
        public ?array $metadata = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            subtotal: isset($data['subtotal']) ? (float) $data['subtotal'] : null,
            tax: isset($data['tax']) ? (float) $data['tax'] : null,
            discount: isset($data['discount']) ? (float) $data['discount'] : null,
            status: $data['status'] ?? null,
            dueDate: isset($data['due_date']) ? new \DateTime($data['due_date']) : null,
            notes: $data['notes'] ?? null,
            metadata: $data['metadata'] ?? null,
        );
    }
}
