<?php

declare(strict_types=1);

namespace Modules\UserManagement\Actions;

use Modules\UserManagement\Models\Role;

/**
 * Action to delete a role
 */
class DeleteRoleAction
{
    /**
     * @throws \Exception
     */
    public function execute(Role $role): bool
    {
        if ($role->is_system) {
            throw new \Exception('System roles cannot be deleted.');
        }

        return $role->delete();
    }
}
