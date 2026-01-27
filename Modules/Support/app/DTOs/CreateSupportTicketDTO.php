<?php

declare(strict_types=1);

namespace Modules\Support\DTOs;

class CreateSupportTicketDTO
{
    public function __construct(
        public readonly string $subject,
        public readonly string $description,
        public readonly string $priority,
        public readonly ?string $category = null,
        public readonly array $attachmentIds = [],
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            subject: $data['subject'],
            description: $data['description'],
            priority: $data['priority'] ?? 'low',
            category: $data['category'] ?? null,
            attachmentIds: $data['attachments'] ?? [],
        );
    }
}
