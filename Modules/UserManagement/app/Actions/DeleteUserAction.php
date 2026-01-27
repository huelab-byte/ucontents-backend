<?php

declare(strict_types=1);

namespace Modules\UserManagement\Actions;

use Modules\UserManagement\Models\User;

/**
 * Action to delete a user
 */
class DeleteUserAction
{
    /**
     * @throws \Exception
     */
    public function execute(User $user): bool
    {
        if (!$user->canBeDeleted()) {
            throw new \Exception('System users cannot be deleted.');
        }

        return $user->delete();
    }
}
