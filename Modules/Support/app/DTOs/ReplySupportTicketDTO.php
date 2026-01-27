<?php

declare(strict_types=1);

namespace Modules\Support\DTOs;

class ReplySupportTicketDTO
{
    public function __construct(
        public readonly ?string $message,
        public readonly array $attachmentIds = [],
        public readonly bool $isInternal = false,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            message: $data['message'] ?? null,
            attachmentIds: $data['attachments'] ?? [],
            isInternal: $data['is_internal'] ?? false,
        );
    }
}
