<?php

declare(strict_types=1);

namespace Modules\UserManagement\DTOs;

/**
 * DTO for listing users with filters
 */
class ListUsersDTO
{
    public function __construct(
        public readonly ?string $role = null,
        public readonly ?string $search = null,
        public readonly int $perPage = 15
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            role: $data['role'] ?? null,
            search: $data['search'] ?? null,
            perPage: isset($data['per_page']) ? (int) $data['per_page'] : 15
        );
    }
}
