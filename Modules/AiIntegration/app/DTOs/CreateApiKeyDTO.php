<?php

declare(strict_types=1);

namespace Modules\AiIntegration\DTOs;

/**
 * Data Transfer Object for creating an AI API key
 */
readonly class CreateApiKeyDTO
{
    public function __construct(
        public int $providerId,
        public string $name,
        public string $apiKey,
        public ?string $apiSecret = null,
        public ?string $endpointUrl = null,
        public ?string $organizationId = null,
        public ?string $projectId = null,
        public bool $isActive = true,
        public int $priority = 0,
        public ?int $rateLimitPerMinute = null,
        public ?int $rateLimitPerDay = null,
        public ?array $metadata = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            providerId: $data['provider_id'],
            name: $data['name'],
            apiKey: $data['api_key'],
            apiSecret: $data['api_secret'] ?? null,
            endpointUrl: $data['endpoint_url'] ?? null,
            organizationId: $data['organization_id'] ?? null,
            projectId: $data['project_id'] ?? null,
            isActive: $data['is_active'] ?? true,
            priority: $data['priority'] ?? 0,
            rateLimitPerMinute: $data['rate_limit_per_minute'] ?? null,
            rateLimitPerDay: $data['rate_limit_per_day'] ?? null,
            metadata: $data['metadata'] ?? null,
        );
    }
}
