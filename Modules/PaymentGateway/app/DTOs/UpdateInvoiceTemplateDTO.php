<?php

declare(strict_types=1);

namespace Modules\PaymentGateway\DTOs;

readonly class UpdateInvoiceTemplateDTO
{
    public function __construct(
        public ?string $name = null,
        public ?string $slug = null,
        public ?string $description = null,
        public ?string $headerHtml = null,
        public ?string $footerHtml = null,
        public ?array $settings = null,
        public ?bool $isActive = null,
        public ?bool $isDefault = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'] ?? null,
            slug: $data['slug'] ?? null,
            description: $data['description'] ?? null,
            headerHtml: $data['header_html'] ?? null,
            footerHtml: $data['footer_html'] ?? null,
            settings: $data['settings'] ?? null,
            isActive: isset($data['is_active']) ? (bool) $data['is_active'] : null,
            isDefault: isset($data['is_default']) ? (bool) $data['is_default'] : null,
        );
    }
}
