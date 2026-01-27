<?php

declare(strict_types=1);

namespace Modules\UserManagement\Actions;

use Modules\UserManagement\Models\Permission;

/**
 * Action to delete a permission
 */
class DeletePermissionAction
{
    /**
     * @throws \Exception
     */
    public function execute(Permission $permission): bool
    {
        // Check if permission is in use
        if ($permission->roles()->exists()) {
            throw new \Exception('Cannot delete permission that is assigned to roles.');
        }

        return $permission->delete();
    }
}
