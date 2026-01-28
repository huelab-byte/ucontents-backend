<?php

declare(strict_types=1);

namespace Modules\BgmLibrary\DTOs;

readonly class UploadBgmDTO
{
    public function __construct(
        public ?int $folderId = null,
        public ?string $title = null,
        public string $metadataSource = 'title',
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            folderId: $data['folder_id'] ?? null,
            title: $data['title'] ?? null,
            metadataSource: $data['metadata_source'] ?? 'title',
        );
    }
}
