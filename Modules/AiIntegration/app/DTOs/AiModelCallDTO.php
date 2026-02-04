<?php

declare(strict_types=1);

namespace Modules\AiIntegration\DTOs;

/**
 * Data Transfer Object for calling an AI model
 */
readonly class AiModelCallDTO
{
    public function __construct(
        public string $providerSlug,
        public string $model,
        public string $prompt,
        public ?int $apiKeyId = null,
        public ?array $settings = null,
        public ?string $module = null,
        public ?string $feature = null,
        public ?array $metadata = null,
        public ?string $scope = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            providerSlug: $data['provider_slug'],
            model: $data['model'],
            prompt: $data['prompt'],
            apiKeyId: $data['api_key_id'] ?? null,
            settings: $data['settings'] ?? null,
            module: $data['module'] ?? null,
            feature: $data['feature'] ?? null,
            metadata: $data['metadata'] ?? null,
            scope: $data['scope'] ?? null,
        );
    }
}

