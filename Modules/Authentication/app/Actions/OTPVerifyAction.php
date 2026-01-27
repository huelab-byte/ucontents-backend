<?php

declare(strict_types=1);

namespace Modules\Authentication\Actions;

use Modules\Authentication\DTOs\OTPVerifyDTO;
use Modules\Authentication\Services\AuthService;
use Modules\Authentication\Services\OTPService;
use Modules\UserManagement\Models\User;

/**
 * Action to verify OTP
 */
class OTPVerifyAction
{
    public function __construct(
        private OTPService $otpService,
        private AuthService $authService
    ) {
    }

    public function execute(OTPVerifyDTO $dto): array
    {
        $user = $this->otpService->verifyOTP(
            code: $dto->code,
            user: $dto->userId ? User::find($dto->userId) : null,
            email: $dto->email,
            type: $dto->type
        );

        // Update last login timestamp
        $user->update(['last_login_at' => now()]);

        $token = $this->authService->createToken($user, 'web');

        return [
            'user' => $user->load('roles.permissions'),
            'token' => $token,
        ];
    }
}
