<?php

declare(strict_types=1);

namespace Modules\Client\DTOs;

/**
 * Data Transfer Object for updating an API client
 */
readonly class UpdateClientDTO
{
    public function __construct(
        public ?string $name = null,
        public ?string $description = null,
        public ?string $environment = null,
        public ?bool $isActive = null,
        public ?array $allowedEndpoints = null,
        public ?array $rateLimit = null,
        public ?\DateTimeInterface $expiresAt = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'] ?? null,
            description: $data['description'] ?? null,
            environment: $data['environment'] ?? null,
            isActive: isset($data['is_active']) ? (bool) $data['is_active'] : null,
            allowedEndpoints: $data['allowed_endpoints'] ?? null,
            rateLimit: $data['rate_limit'] ?? null,
            expiresAt: isset($data['expires_at']) ? new \DateTime($data['expires_at']) : null,
        );
    }

    public function toArray(): array
    {
        $data = [];

        if ($this->name !== null) {
            $data['name'] = $this->name;
        }
        if ($this->description !== null) {
            $data['description'] = $this->description;
        }
        if ($this->environment !== null) {
            $data['environment'] = $this->environment;
        }
        if ($this->isActive !== null) {
            $data['is_active'] = $this->isActive;
        }
        if ($this->allowedEndpoints !== null) {
            $data['allowed_endpoints'] = $this->allowedEndpoints;
        }
        if ($this->rateLimit !== null) {
            $data['rate_limit'] = $this->rateLimit;
        }
        if ($this->expiresAt !== null) {
            $data['expires_at'] = $this->expiresAt->format('Y-m-d H:i:s');
        }

        return $data;
    }
}
