<?php

declare(strict_types=1);

namespace Modules\UserManagement\Services;

use Modules\UserManagement\Models\Role;

/**
 * Service for role management operations
 */
class RoleService
{
    /**
     * Create a new role with permissions
     */
    public function createRole(
        string $name,
        string $slug,
        ?string $description = null,
        int $hierarchy = 0,
        ?array $permissionSlugs = null
    ): Role {
        $role = Role::create([
            'name' => $name,
            'slug' => $slug,
            'description' => $description,
            'hierarchy' => $hierarchy,
            'is_system' => false,
        ]);

        if ($permissionSlugs) {
            $role->syncPermissions($permissionSlugs);
        }

        return $role->load('permissions');
    }

    /**
     * Update role information
     */
    public function updateRole(Role $role, array $data): Role
    {
        $updateData = array_filter([
            'name' => $data['name'] ?? null,
            'description' => $data['description'] ?? null,
            'hierarchy' => $data['hierarchy'] ?? null,
        ], fn($value) => $value !== null);

        if (!empty($updateData)) {
            $role->update($updateData);
        }

        if (isset($data['permissions'])) {
            $role->syncPermissions($data['permissions']);
        }

        return $role->fresh()->load('permissions');
    }

    /**
     * Assign permission to role
     */
    public function assignPermission(Role $role, string $permissionSlug): void
    {
        $role->assignPermission($permissionSlug);
    }

    /**
     * Remove permission from role
     */
    public function removePermission(Role $role, string $permissionSlug): void
    {
        $role->removePermission($permissionSlug);
    }

    /**
     * Sync role permissions
     */
    public function syncPermissions(Role $role, array $permissionSlugs): void
    {
        $role->syncPermissions($permissionSlugs);
    }
}
