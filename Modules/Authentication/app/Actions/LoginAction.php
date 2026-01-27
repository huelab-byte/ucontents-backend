<?php

declare(strict_types=1);

namespace Modules\Authentication\Actions;

use Modules\Authentication\DTOs\LoginDTO;
use Modules\Authentication\Services\AuthService;
use Modules\Authentication\Services\TwoFactorService;
use Modules\UserManagement\Models\User;

/**
 * Action to login a user
 */
class LoginAction
{
    public function __construct(
        private AuthService $authService,
        private TwoFactorService $twoFactorService
    ) {
    }

    public function execute(LoginDTO $dto): array
    {
        $user = $this->authService->login(
            email: $dto->email,
            password: $dto->password,
            remember: $dto->remember
        );

        // Check if 2FA is required
        $is2FARequired = $this->twoFactorService->is2FARequired($user);
        $is2FAEnabled = $this->twoFactorService->is2FAEnabled($user);

        // If 2FA is required but not enabled, return response indicating setup is needed
        // Issue a temporary token that allows access to 2FA setup endpoints only
        if ($is2FARequired && !$is2FAEnabled) {
            $tempToken = $this->authService->createToken($user, '2fa-setup');
            return [
                'user' => $user->load(['roles.permissions']),
                'token' => $tempToken,
                'requires_2fa_setup' => true,
                'message' => 'Two-factor authentication is required. Please set it up to continue.',
            ];
        }

        // If 2FA is enabled, return response indicating OTP verification is needed
        if ($is2FAEnabled) {
            return [
                'user' => $user->load(['roles.permissions']),
                'token' => null,
                'requires_2fa_verification' => true,
                'message' => 'Please enter your 2FA code to complete login.',
            ];
        }

        // Normal login - create token
        $token = $this->authService->createToken($user, 'web');

        return [
            'user' => $user->load(['roles.permissions']),
            'token' => $token,
        ];
    }
}
