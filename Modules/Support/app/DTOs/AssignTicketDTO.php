<?php

declare(strict_types=1);

namespace Modules\Support\DTOs;

class AssignTicketDTO
{
    public function __construct(
        public readonly ?int $assignedToUserId,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            assignedToUserId: $data['assigned_to_user_id'] ?? null,
        );
    }
}
