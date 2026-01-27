<?php

declare(strict_types=1);

namespace Modules\ImageLibrary\DTOs;

readonly class UploadImageDTO
{
    public function __construct(
        public ?int $folderId = null,
        public ?string $title = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            folderId: $data['folder_id'] ?? null,
            title: $data['title'] ?? null,
        );
    }
}
