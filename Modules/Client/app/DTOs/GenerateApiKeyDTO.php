<?php

declare(strict_types=1);

namespace Modules\Client\DTOs;

/**
 * Data Transfer Object for generating an API key
 */
readonly class GenerateApiKeyDTO
{
    public function __construct(
        public int $apiClientId,
        public ?string $name = null,
        public ?\DateTimeInterface $expiresAt = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            apiClientId: (int) $data['api_client_id'],
            name: $data['name'] ?? null,
            expiresAt: isset($data['expires_at']) ? new \DateTime($data['expires_at']) : null,
        );
    }
}
