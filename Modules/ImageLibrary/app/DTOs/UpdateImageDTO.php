<?php

declare(strict_types=1);

namespace Modules\ImageLibrary\DTOs;

readonly class UpdateImageDTO
{
    public function __construct(
        public ?string $title = null,
        public ?int $folderId = null,
        public ?array $metadata = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            title: $data['title'] ?? null,
            folderId: $data['folder_id'] ?? null,
            metadata: $data['metadata'] ?? null,
        );
    }
}
