<?php

declare(strict_types=1);

namespace Modules\Authentication\DTOs;

/**
 * Data Transfer Object for password reset
 */
readonly class PasswordResetDTO
{
    public function __construct(
        public string $email,
        public string $token,
        public string $password,
        public string $passwordConfirmation,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            email: $data['email'],
            token: $data['token'],
            password: $data['password'],
            passwordConfirmation: $data['password_confirmation'],
        );
    }
}
