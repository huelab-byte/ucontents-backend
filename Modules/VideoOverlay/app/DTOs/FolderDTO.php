<?php

declare(strict_types=1);

namespace Modules\VideoOverlay\DTOs;

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
            parentId: isset($data['parent_id']) ? (int) $data['parent_id'] : null,
        );
    }
}
