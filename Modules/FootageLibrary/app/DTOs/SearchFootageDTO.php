<?php

declare(strict_types=1);

namespace Modules\FootageLibrary\DTOs;

readonly class SearchFootageDTO
{
    public function __construct(
        public string $searchText,
        public float $contentLength, // Length of the long video in seconds
        public ?int $folderId = null,
        public ?string $orientation = null, // 'horizontal' or 'vertical'
        public ?float $footageLength = null, // Desired footage length in seconds
        public ?int $userId = null, // For AI key selection: use this user's keys when set
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            searchText: $data['search_text'] ?? $data['content'] ?? '',
            contentLength: (float) ($data['content_length'] ?? 0),
            folderId: $data['folder_id'] ?? null,
            orientation: $data['orientation'] ?? null,
            footageLength: isset($data['footage_length']) ? (float) $data['footage_length'] : null,
            userId: $data['user_id'] ?? null,
        );
    }
}
