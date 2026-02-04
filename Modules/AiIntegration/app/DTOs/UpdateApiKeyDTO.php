<?php

declare(strict_types=1);

namespace Modules\AiIntegration\DTOs;

/**
 * Data Transfer Object for updating an AI API key
 */
readonly class UpdateApiKeyDTO
{
    public function __construct(
        public ?string $name = null,
        public ?string $apiKey = null,
        public ?string $apiSecret = null,
        public ?string $endpointUrl = null,
        public ?string $organizationId = null,
        public ?string $projectId = null,
        public ?bool $isActive = null,
        public ?int $priority = null,
        public ?int $rateLimitPerMinute = null,
        public ?int $rateLimitPerDay = null,
        public ?array $metadata = null,
        public ?array $scopes = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'] ?? null,
            apiKey: $data['api_key'] ?? null,
            apiSecret: $data['api_secret'] ?? null,
            endpointUrl: $data['endpoint_url'] ?? null,
            organizationId: $data['organization_id'] ?? null,
            projectId: $data['project_id'] ?? null,
            isActive: $data['is_active'] ?? null,
            priority: $data['priority'] ?? null,
            rateLimitPerMinute: $data['rate_limit_per_minute'] ?? null,
            rateLimitPerDay: $data['rate_limit_per_day'] ?? null,
            metadata: $data['metadata'] ?? null,
            scopes: array_key_exists('scopes', $data) ? $data['scopes'] : null,
        );
    }
}
