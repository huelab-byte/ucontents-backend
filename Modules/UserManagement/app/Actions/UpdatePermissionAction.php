<?php

declare(strict_types=1);

namespace Modules\UserManagement\Actions;

use Modules\UserManagement\Models\Permission;

/**
 * Action to update a permission
 */
class UpdatePermissionAction
{
    public function execute(Permission $permission, array $data): Permission
    {
        $permission->update($data);

        return $permission->fresh();
    }
}
