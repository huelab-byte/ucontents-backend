<?php

declare(strict_types=1);

namespace Modules\UserManagement\DTOs;

/**
 * Data Transfer Object for updating a role
 */
readonly class UpdateRoleDTO
{
    public function __construct(
        public ?string $name = null,
        public ?string $description = null,
        public ?int $hierarchy = null,
        public ?array $permissionSlugs = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'] ?? null,
            description: $data['description'] ?? null,
            hierarchy: isset($data['hierarchy']) ? (int) $data['hierarchy'] : null,
            permissionSlugs: $data['permissions'] ?? null,
        );
    }

    public function toArray(): array
    {
        $data = [];

        if ($this->name !== null) {
            $data['name'] = $this->name;
        }
        if ($this->description !== null) {
            $data['description'] = $this->description;
        }
        if ($this->hierarchy !== null) {
            $data['hierarchy'] = $this->hierarchy;
        }

        return $data;
    }
}
