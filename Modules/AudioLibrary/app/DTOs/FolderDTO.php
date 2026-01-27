<?php

declare(strict_types=1);

namespace Modules\AudioLibrary\DTOs;

readonly class FolderDTO
{
    public function __construct(
        public string $name,
        public ?int $parentId = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            parentId: $data['parent_id'] ?? null,
        );
    }
}
