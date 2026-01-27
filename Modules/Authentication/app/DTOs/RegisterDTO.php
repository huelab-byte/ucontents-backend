<?php

declare(strict_types=1);

namespace Modules\Authentication\DTOs;

/**
 * Data Transfer Object for registration
 */
readonly class RegisterDTO
{
    public function __construct(
        public string $name,
        public string $email,
        public string $password,
        public ?string $passwordConfirmation = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            email: $data['email'],
            password: $data['password'],
            passwordConfirmation: $data['password_confirmation'] ?? null,
        );
    }
}
