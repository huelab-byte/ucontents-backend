<?php

declare(strict_types=1);

namespace Modules\UserManagement\Http\Controllers\Api\V1\Admin;

use Illuminate\Http\JsonResponse;
use Modules\Core\Http\Controllers\Api\BaseApiController;
use Modules\UserManagement\Actions\CreatePermissionAction;
use Modules\UserManagement\Actions\DeletePermissionAction;
use Modules\UserManagement\Actions\UpdatePermissionAction;
use Modules\UserManagement\Http\Requests\ListPermissionsRequest;
use Modules\UserManagement\Http\Requests\StorePermissionRequest;
use Modules\UserManagement\Http\Requests\UpdatePermissionRequest;
use Modules\UserManagement\Http\Resources\PermissionResource;
use Modules\UserManagement\Models\Permission;

/**
 * Admin API Controller for managing permissions
 */
class PermissionController extends BaseApiController
{
    public function __construct(
        private CreatePermissionAction $createPermissionAction,
        private UpdatePermissionAction $updatePermissionAction,
        private DeletePermissionAction $deletePermissionAction
    ) {
    }
    /**
     * List all permissions
     */
    public function index(ListPermissionsRequest $request): JsonResponse
    {
        $this->authorize('viewAny', Permission::class);

        $validated = $request->validated();
        $query = Permission::withCount('roles');

        // Filter by module
        if (isset($validated['module'])) {
            $query->where('module', $validated['module']);
        }

        // Search by name or slug
        if (isset($validated['search'])) {
            $search = $validated['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('slug', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Group by module
        if (isset($validated['group_by_module']) && $validated['group_by_module']) {
            $permissions = $query->orderBy('module')->orderBy('name')->get();
            $grouped = $permissions->groupBy('module');
            
            return $this->success($grouped, 'Permissions retrieved successfully');
        }

        $permissions = $query->orderBy('module')->orderBy('name')->paginate($validated['per_page'] ?? 50);

        return $this->paginatedResource($permissions, PermissionResource::class, 'Permissions retrieved successfully');
    }

    /**
     * Show a specific permission
     */
    public function show(Permission $permission): JsonResponse
    {
        $this->authorize('view', $permission);

        $permission->load('roles');

        return $this->success(new PermissionResource($permission), 'Permission retrieved successfully');
    }

    /**
     * Create a new permission
     */
    public function store(StorePermissionRequest $request): JsonResponse
    {
        $this->authorize('create', Permission::class);

        $permission = $this->createPermissionAction->execute($request->validated());

        return $this->success(new PermissionResource($permission), 'Permission created successfully', 201);
    }

    /**
     * Update a permission
     */
    public function update(UpdatePermissionRequest $request, Permission $permission): JsonResponse
    {
        $this->authorize('update', $permission);

        $permission = $this->updatePermissionAction->execute($permission, $request->validated());

        return $this->success(new PermissionResource($permission), 'Permission updated successfully');
    }

    /**
     * Delete a permission
     */
    public function destroy(Permission $permission): JsonResponse
    {
        $this->authorize('delete', $permission);

        try {
            $this->deletePermissionAction->execute($permission);
            return $this->success(null, 'Permission deleted successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 422);
        }
    }

    /**
     * Get all modules that have permissions
     */
    public function modules(): JsonResponse
    {
        $this->authorize('viewAny', Permission::class);

        $modules = Permission::distinct()
            ->whereNotNull('module')
            ->orderBy('module')
            ->pluck('module')
            ->toArray();

        return $this->success($modules, 'Modules retrieved successfully');
    }
}
