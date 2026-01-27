<?php

declare(strict_types=1);

namespace Modules\EmailManagement\DTOs;

/**
 * Data Transfer Object for SMTP Configuration
 */
readonly class SmtpConfigurationDTO
{
    public function __construct(
        public string $name,
        public string $host,
        public int $port,
        public string $encryption,
        public string $username,
        public ?string $password,
        public string $fromAddress,
        public ?string $fromName = null,
        public bool $isActive = false,
        public bool $isDefault = false,
        public ?array $options = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            host: $data['host'],
            port: (int) ($data['port'] ?? 587),
            encryption: $data['encryption'] ?? 'tls',
            username: $data['username'],
            password: $data['password'] ?? null,
            fromAddress: $data['from_address'],
            fromName: $data['from_name'] ?? null,
            isActive: $data['is_active'] ?? false,
            isDefault: $data['is_default'] ?? false,
            options: $data['options'] ?? null,
        );
    }
}
