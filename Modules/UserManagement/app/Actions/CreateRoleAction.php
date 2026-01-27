<?php

declare(strict_types=1);

namespace Modules\UserManagement\Actions;

use Modules\UserManagement\DTOs\CreateRoleDTO;
use Modules\UserManagement\Models\Role;
use Modules\UserManagement\Services\RoleService;

/**
 * Action to create a new role
 */
class CreateRoleAction
{
    public function __construct(
        private RoleService $roleService
    ) {
    }

    public function execute(CreateRoleDTO $dto): Role
    {
        return $this->roleService->createRole(
            name: $dto->name,
            slug: $dto->slug,
            description: $dto->description,
            hierarchy: $dto->hierarchy,
            permissionSlugs: $dto->permissionSlugs
        );
    }
}
