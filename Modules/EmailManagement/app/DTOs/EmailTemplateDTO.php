<?php

declare(strict_types=1);

namespace Modules\EmailManagement\DTOs;

/**
 * Data Transfer Object for Email Template
 */
readonly class EmailTemplateDTO
{
    public function __construct(
        public string $name,
        public ?string $slug = null,
        public string $subject,
        public string $bodyHtml,
        public ?string $bodyText = null,
        public ?array $variables = null,
        public string $category = 'general',
        public bool $isActive = true,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            slug: $data['slug'] ?? null,
            subject: $data['subject'],
            bodyHtml: $data['body_html'],
            bodyText: $data['body_text'] ?? null,
            variables: $data['variables'] ?? null,
            category: $data['category'] ?? 'general',
            isActive: $data['is_active'] ?? true,
        );
    }
}
