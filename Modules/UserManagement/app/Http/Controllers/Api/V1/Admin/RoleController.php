<?php

declare(strict_types=1);

namespace Modules\UserManagement\Http\Controllers\Api\V1\Admin;

use Illuminate\Http\JsonResponse;
use Modules\Core\Http\Controllers\Api\BaseApiController;
use Modules\UserManagement\Actions\CreateRoleAction;
use Modules\UserManagement\Actions\DeleteRoleAction;
use Modules\UserManagement\Actions\UpdateRoleAction;
use Modules\UserManagement\DTOs\CreateRoleDTO;
use Modules\UserManagement\DTOs\UpdateRoleDTO;
use Modules\UserManagement\Http\Requests\ListRolesRequest;
use Modules\UserManagement\Http\Requests\StoreRoleRequest;
use Modules\UserManagement\Http\Requests\UpdateRoleRequest;
use Modules\UserManagement\Http\Resources\PermissionResource;
use Modules\UserManagement\Http\Resources\RoleResource;
use Modules\UserManagement\Models\Permission;
use Modules\UserManagement\Models\Role;

/**
 * Admin API Controller for managing roles
 */
class RoleController extends BaseApiController
{
    public function __construct(
        private CreateRoleAction $createRoleAction,
        private UpdateRoleAction $updateRoleAction,
        private DeleteRoleAction $deleteRoleAction
    ) {
    }

    /**
     * List all roles
     */
    public function index(ListRolesRequest $request): JsonResponse
    {
        $this->authorize('viewAny', Role::class);

        $validated = $request->validated();
        $query = Role::with('permissions')->withCount('users');

        // Search by name
        if (isset($validated['search'])) {
            $query->where('name', 'like', '%' . $validated['search'] . '%');
        }

        $roles = $query->orderBy('hierarchy', 'desc')->paginate($validated['per_page'] ?? 15);

        return $this->paginatedResource($roles, RoleResource::class, 'Roles retrieved successfully');
    }

    /**
     * Show a specific role
     */
    public function show(Role $role): JsonResponse
    {
        $this->authorize('view', $role);

        $role->load('permissions', 'users');

        return $this->success(new RoleResource($role), 'Role retrieved successfully');
    }

    /**
     * Create a new role
     */
    public function store(StoreRoleRequest $request): JsonResponse
    {
        $this->authorize('create', Role::class);

        $dto = CreateRoleDTO::fromArray($request->validated());
        $role = $this->createRoleAction->execute($dto);

        return $this->success(new RoleResource($role), 'Role created successfully', 201);
    }

    /**
     * Update a role
     */
    public function update(UpdateRoleRequest $request, Role $role): JsonResponse
    {
        $this->authorize('update', $role);

        if ($role->is_system) {
            return $this->error('System roles cannot be modified', 403);
        }

        $dto = UpdateRoleDTO::fromArray($request->validated());
        $role = $this->updateRoleAction->execute($role, $dto);

        return $this->success(new RoleResource($role), 'Role updated successfully');
    }

    /**
     * Delete a role
     */
    public function destroy(Role $role): JsonResponse
    {
        $this->authorize('delete', $role);

        try {
            $this->deleteRoleAction->execute($role);
            return $this->success(null, 'Role deleted successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 403);
        }
    }

    /**
     * List all permissions
     */
    public function permissions(): JsonResponse
    {
        $this->authorize('viewAny', Permission::class);

        $permissions = Permission::all();

        return $this->success(PermissionResource::collection($permissions), 'Permissions retrieved successfully');
    }
}
