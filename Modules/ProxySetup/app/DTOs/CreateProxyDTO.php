<?php

declare(strict_types=1);

namespace Modules\ProxySetup\DTOs;

readonly class CreateProxyDTO
{
    public function __construct(
        public string $name,
        public string $type,
        public string $host,
        public int $port,
        public ?string $username = null,
        public ?string $password = null,
        public bool $isEnabled = true,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            type: $data['type'] ?? 'http',
            host: $data['host'],
            port: (int) $data['port'],
            username: $data['username'] ?? null,
            password: $data['password'] ?? null,
            isEnabled: $data['is_enabled'] ?? true,
        );
    }
}
