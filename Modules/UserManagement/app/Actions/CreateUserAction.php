<?php

declare(strict_types=1);

namespace Modules\UserManagement\Actions;

use Modules\UserManagement\DTOs\CreateUserDTO;
use Modules\UserManagement\Models\User;
use Modules\UserManagement\Services\UserService;

/**
 * Action to create a new user
 */
class CreateUserAction
{
    public function __construct(
        private UserService $userService
    ) {
    }

    public function execute(CreateUserDTO $dto): User
    {
        return $this->userService->createUser(
            name: $dto->name,
            email: $dto->email,
            password: $dto->password,
            roleSlugs: $dto->roleSlugs,
            status: $dto->status,
            sendSetPasswordEmail: $dto->sendSetPasswordEmail
        );
    }
}
