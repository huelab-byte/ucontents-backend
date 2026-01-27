<?php

declare(strict_types=1);

namespace Modules\Authentication\Actions;

use Modules\Authentication\Services\AuthService;
use Modules\UserManagement\Models\User;

/**
 * Action to logout a user
 */
class LogoutAction
{
    public function __construct(
        private AuthService $authService
    ) {
    }

    public function execute(User $user): void
    {
        $this->authService->logout($user);
    }
}
