<?php

declare(strict_types=1);

namespace Modules\Authentication\DTOs;

/**
 * Data Transfer Object for OTP verification
 */
readonly class OTPVerifyDTO
{
    public function __construct(
        public string $code,
        public ?string $email = null,
        public ?int $userId = null,
        public string $type = 'login',
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            code: $data['code'],
            email: $data['email'] ?? null,
            userId: isset($data['user_id']) ? (int) $data['user_id'] : null,
            type: $data['type'] ?? 'login',
        );
    }
}
