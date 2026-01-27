<?php

declare(strict_types=1);

namespace Modules\Support\DTOs;

/**
 * DTO for listing support tickets with filters
 */
class ListSupportTicketsDTO
{
    public function __construct(
        public readonly ?string $status = null,
        public readonly ?string $priority = null,
        public readonly ?int $assignedTo = null,
        public readonly ?int $userId = null,
        public readonly ?string $search = null,
        public readonly int $perPage = 15
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            status: $data['status'] ?? null,
            priority: $data['priority'] ?? null,
            assignedTo: isset($data['assigned_to']) ? (int) $data['assigned_to'] : null,
            userId: isset($data['user_id']) ? (int) $data['user_id'] : null,
            search: $data['search'] ?? null,
            perPage: isset($data['per_page']) ? (int) $data['per_page'] : 15
        );
    }
}
