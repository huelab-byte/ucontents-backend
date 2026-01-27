<?php

declare(strict_types=1);

namespace Modules\Authentication\Actions;

use Modules\Authentication\Services\AuthService;
use Modules\Authentication\Services\TokenService;
use Modules\UserManagement\Models\User;

/**
 * Action to verify magic link and login user
 */
class MagicLinkVerifyAction
{
    public function __construct(
        private TokenService $tokenService,
        private AuthService $authService
    ) {
    }

    public function execute(string $token): array
    {
        $user = $this->tokenService->verifyMagicLinkToken($token);
        
        // Update last login timestamp
        $user->update(['last_login_at' => now()]);
        
        $token = $this->authService->createToken($user, 'web');

        return [
            'user' => $user->load('roles.permissions'),
            'token' => $token,
        ];
    }
}
