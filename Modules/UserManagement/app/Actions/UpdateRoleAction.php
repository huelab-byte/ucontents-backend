<?php

declare(strict_types=1);

namespace Modules\UserManagement\Actions;

use Modules\UserManagement\DTOs\UpdateRoleDTO;
use Modules\UserManagement\Models\Role;
use Modules\UserManagement\Services\RoleService;

/**
 * Action to update a role
 */
class UpdateRoleAction
{
    public function __construct(
        private RoleService $roleService
    ) {
    }

    public function execute(Role $role, UpdateRoleDTO $dto): Role
    {
        $data = $dto->toArray();
        if ($dto->permissionSlugs !== null) {
            $data['permissions'] = $dto->permissionSlugs;
        }

        return $this->roleService->updateRole($role, $data);
    }
}
