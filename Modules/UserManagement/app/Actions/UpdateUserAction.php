<?php

declare(strict_types=1);

namespace Modules\UserManagement\Actions;

use Modules\UserManagement\DTOs\UpdateUserDTO;
use Modules\UserManagement\Models\User;
use Modules\UserManagement\Services\UserService;

/**
 * Action to update a user
 */
class UpdateUserAction
{
    public function __construct(
        private UserService $userService
    ) {
    }

    public function execute(User $user, UpdateUserDTO $dto): User
    {
        $data = $dto->toArray();
        if ($dto->roleSlugs !== null) {
            $data['roles'] = $dto->roleSlugs;
        }

        return $this->userService->updateUser($user, $data);
    }
}
