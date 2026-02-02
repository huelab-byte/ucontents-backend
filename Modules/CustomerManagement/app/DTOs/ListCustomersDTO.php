<?php

declare(strict_types=1);

namespace Modules\CustomerManagement\DTOs;

/**
 * DTO for listing customers with filters
 */
class ListCustomersDTO
{
    public function __construct(
        public readonly ?string $search = null,
        public readonly ?string $status = null,
        public readonly int $perPage = 15,
        public readonly int $page = 1
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            search: $data['search'] ?? null,
            status: $data['status'] ?? null,
            perPage: isset($data['per_page']) ? (int) $data['per_page'] : 15,
            page: isset($data['page']) ? (int) $data['page'] : 1
        );
    }
}
