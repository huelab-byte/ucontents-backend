<?php

declare(strict_types=1);

namespace Modules\UserManagement\DTOs;

/**
 * Data Transfer Object for creating a role
 */
readonly class CreateRoleDTO
{
    public function __construct(
        public string $name,
        public string $slug,
        public ?string $description = null,
        public int $hierarchy = 0,
        public ?array $permissionSlugs = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            slug: $data['slug'],
            description: $data['description'] ?? null,
            hierarchy: $data['hierarchy'] ?? 0,
            permissionSlugs: $data['permissions'] ?? null,
        );
    }
}
