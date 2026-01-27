<?php

declare(strict_types=1);

namespace Modules\Authentication\DTOs;

/**
 * Data Transfer Object for password reset request
 */
readonly class PasswordResetRequestDTO
{
    public function __construct(
        public string $email,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            email: $data['email'],
        );
    }
}
