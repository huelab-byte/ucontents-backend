<?php

declare(strict_types=1);

namespace Modules\SocialConnection\DTOs;

/**
 * DTO for saving selected channels from OAuth callback
 */
class SaveSelectedChannelsDTO
{
    public function __construct(
        public readonly string $token,
        public readonly array $selectedChannels,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            token: $data['token'],
            selectedChannels: $data['selected_channels'] ?? [],
        );
    }
}
