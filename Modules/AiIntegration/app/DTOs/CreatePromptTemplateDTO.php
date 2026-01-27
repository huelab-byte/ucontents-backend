<?php

declare(strict_types=1);

namespace Modules\AiIntegration\DTOs;

/**
 * Data Transfer Object for creating a prompt template
 */
readonly class CreatePromptTemplateDTO
{
    public function __construct(
        public string $name,
        public string $slug,
        public string $template,
        public ?string $description = null,
        public ?array $variables = null,
        public ?string $category = null,
        public ?string $providerSlug = null,
        public ?string $model = null,
        public ?array $settings = null,
        public bool $isActive = true,
        public ?int $createdBy = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            slug: $data['slug'],
            template: $data['template'],
            description: $data['description'] ?? null,
            variables: $data['variables'] ?? null,
            category: $data['category'] ?? null,
            providerSlug: $data['provider_slug'] ?? null,
            model: $data['model'] ?? null,
            settings: $data['settings'] ?? null,
            isActive: $data['is_active'] ?? true,
            createdBy: $data['created_by'] ?? null,
        );
    }
}
