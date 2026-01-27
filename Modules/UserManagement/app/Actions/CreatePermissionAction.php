<?php

declare(strict_types=1);

namespace Modules\UserManagement\Actions;

use Modules\UserManagement\Models\Permission;

/**
 * Action to create a new permission
 */
class CreatePermissionAction
{
    public function execute(array $data): Permission
    {
        return Permission::create($data);
    }
}
