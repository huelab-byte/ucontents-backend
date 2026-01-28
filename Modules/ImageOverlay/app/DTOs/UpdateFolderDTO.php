<?php

declare(strict_types=1);

namespace Modules\ImageOverlay\DTOs;

readonly class UpdateFolderDTO
{
    public function __construct(
        public ?string $name = null,
        public ?int $parentId = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'] ?? null,
            parentId: $data['parent_id'] ?? null,
        );
    }
}
