<?php

declare(strict_types=1);

namespace Modules\PaymentGateway\DTOs;

/**
 * Data Transfer Object for configuring a payment gateway
 */
readonly class ConfigureGatewayDTO
{
    public function __construct(
        public string $name,
        public ?string $displayName = null,
        public ?bool $isActive = null,
        public ?bool $isTestMode = null,
        public ?array $credentials = null,
        public ?array $settings = null,
        public ?string $description = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'] ?? throw new \InvalidArgumentException('Name is required'),
            displayName: $data['display_name'] ?? null,
            isActive: isset($data['is_active']) ? (bool) $data['is_active'] : null,
            isTestMode: isset($data['is_test_mode']) ? (bool) $data['is_test_mode'] : null,
            credentials: $data['credentials'] ?? null,
            settings: $data['settings'] ?? null,
            description: $data['description'] ?? null,
        );
    }
}
