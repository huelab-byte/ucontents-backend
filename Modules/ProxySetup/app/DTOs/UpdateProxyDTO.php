<?php

declare(strict_types=1);

namespace Modules\ProxySetup\DTOs;

readonly class UpdateProxyDTO
{
    public function __construct(
        public ?string $name = null,
        public ?string $type = null,
        public ?string $host = null,
        public ?int $port = null,
        public ?string $username = null,
        public ?string $password = null,
        public ?bool $isEnabled = null,
        public bool $clearUsername = false,
        public bool $clearPassword = false,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'] ?? null,
            type: $data['type'] ?? null,
            host: $data['host'] ?? null,
            port: isset($data['port']) ? (int) $data['port'] : null,
            username: $data['username'] ?? null,
            password: $data['password'] ?? null,
            isEnabled: $data['is_enabled'] ?? null,
            clearUsername: ($data['username'] ?? null) === '',
            clearPassword: ($data['password'] ?? null) === '',
        );
    }
}
