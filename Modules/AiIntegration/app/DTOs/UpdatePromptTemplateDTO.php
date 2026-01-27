<?php

declare(strict_types=1);

namespace Modules\AiIntegration\DTOs;

/**
 * Data Transfer Object for updating a prompt template
 */
readonly class UpdatePromptTemplateDTO
{
    public function __construct(
        public ?string $name = null,
        public ?string $template = null,
        public ?string $description = null,
        public ?array $variables = null,
        public ?string $category = null,
        public ?string $providerSlug = null,
        public ?string $model = null,
        public ?array $settings = null,
        public ?bool $isActive = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'] ?? null,
            template: $data['template'] ?? null,
            description: $data['description'] ?? null,
            variables: $data['variables'] ?? null,
            category: $data['category'] ?? null,
            providerSlug: $data['provider_slug'] ?? null,
            model: $data['model'] ?? null,
            settings: $data['settings'] ?? null,
            isActive: $data['is_active'] ?? null,
        );
    }
}
