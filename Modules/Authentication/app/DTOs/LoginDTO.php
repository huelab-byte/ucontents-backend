<?php

declare(strict_types=1);

namespace Modules\Authentication\DTOs;

/**
 * Data Transfer Object for login
 */
readonly class LoginDTO
{
    public function __construct(
        public string $email,
        public string $password,
        public bool $remember = false,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            email: $data['email'],
            password: $data['password'],
            remember: $data['remember'] ?? false,
        );
    }
}
