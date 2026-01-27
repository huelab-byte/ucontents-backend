<?php

declare(strict_types=1);

namespace Modules\PaymentGateway\DTOs;

readonly class CreateInvoiceTemplateDTO
{
    public function __construct(
        public string $name,
        public string $slug,
        public ?string $description = null,
        public ?string $headerHtml = null,
        public ?string $footerHtml = null,
        public ?array $settings = null,
        public bool $isActive = true,
        public bool $isDefault = false,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            slug: $data['slug'],
            description: $data['description'] ?? null,
            headerHtml: $data['header_html'] ?? null,
            footerHtml: $data['footer_html'] ?? null,
            settings: $data['settings'] ?? null,
            isActive: $data['is_active'] ?? true,
            isDefault: $data['is_default'] ?? false,
        );
    }
}
