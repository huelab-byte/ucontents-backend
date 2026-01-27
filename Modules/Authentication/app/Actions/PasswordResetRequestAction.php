<?php

declare(strict_types=1);

namespace Modules\Authentication\Actions;

use Modules\Authentication\DTOs\PasswordResetRequestDTO;
use Modules\Authentication\Services\TokenService;

/**
 * Action to request password reset
 */
class PasswordResetRequestAction
{
    public function __construct(
        private TokenService $tokenService
    ) {
    }

    public function execute(PasswordResetRequestDTO $dto): string
    {
        return $this->tokenService->sendPasswordResetToken($dto->email);
    }
}
