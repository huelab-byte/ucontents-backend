<?php

declare(strict_types=1);

namespace Modules\Authentication\Actions;

use Modules\Authentication\DTOs\PasswordResetDTO;
use Modules\Authentication\Services\TokenService;

/**
 * Action to reset password
 */
class PasswordResetAction
{
    public function __construct(
        private TokenService $tokenService
    ) {
    }

    public function execute(PasswordResetDTO $dto): void
    {
        $this->tokenService->resetPassword(
            email: $dto->email,
            token: $dto->token,
            password: $dto->password,
            passwordConfirmation: $dto->passwordConfirmation
        );
    }
}
