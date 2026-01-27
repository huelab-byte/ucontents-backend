<?php

declare(strict_types=1);

namespace Modules\Support\DTOs;

class UpdateTicketPriorityDTO
{
    public function __construct(
        public readonly string $priority,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            priority: $data['priority'],
        );
    }
}
