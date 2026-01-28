<?php

declare(strict_types=1);

namespace Modules\VideoOverlay\DTOs;

readonly class UpdateVideoOverlayDTO
{
    public function __construct(
        public ?string $title = null,
        public ?int $folderId = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            title: $data['title'] ?? null,
            folderId: isset($data['folder_id']) ? (int) $data['folder_id'] : null,
        );
    }
}
