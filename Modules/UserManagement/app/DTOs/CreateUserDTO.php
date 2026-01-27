<?php

declare(strict_types=1);

namespace Modules\UserManagement\DTOs;

use Modules\UserManagement\Models\User;

/**
 * Data Transfer Object for creating a user
 */
readonly class CreateUserDTO
{
    public function __construct(
        public string $name,
        public string $email,
        public ?string $password = null,
        public ?array $roleSlugs = null,
        public string $status = User::STATUS_ACTIVE,
        public bool $sendSetPasswordEmail = true,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            email: $data['email'],
            password: $data['password'] ?? null,
            roleSlugs: $data['roles'] ?? null,
            status: $data['status'] ?? User::STATUS_ACTIVE,
            sendSetPasswordEmail: $data['send_set_password_email'] ?? true,
        );
    }
}
