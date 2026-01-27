<?php

declare(strict_types=1);

namespace Modules\Client\DTOs;

/**
 * Data Transfer Object for creating an API client
 */
readonly class CreateClientDTO
{
    public function __construct(
        public string $name,
        public ?string $description = null,
        public string $environment = 'production',
        public ?array $allowedEndpoints = null,
        public ?array $rateLimit = null,
        public ?\DateTimeInterface $expiresAt = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            description: $data['description'] ?? null,
            environment: $data['environment'] ?? 'production',
            allowedEndpoints: $data['allowed_endpoints'] ?? null,
            rateLimit: $data['rate_limit'] ?? null,
            expiresAt: isset($data['expires_at']) ? new \DateTime($data['expires_at']) : null,
        );
    }
}
