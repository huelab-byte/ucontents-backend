<?php

declare(strict_types=1);

namespace Modules\UserManagement\Http\Controllers\Api\V1\Admin;

use Illuminate\Http\JsonResponse;
use Modules\Core\Http\Controllers\Api\BaseApiController;
use Modules\UserManagement\Actions\CreateUserAction;
use Modules\UserManagement\Actions\DeleteUserAction;
use Modules\UserManagement\Actions\ListUsersAction;
use Modules\UserManagement\Actions\UpdateUserAction;
use Modules\UserManagement\DTOs\CreateUserDTO;
use Modules\UserManagement\DTOs\ListUsersDTO;
use Modules\UserManagement\DTOs\UpdateUserDTO;
use Modules\UserManagement\Http\Requests\ListUsersRequest;
use Modules\UserManagement\Http\Requests\StoreUserRequest;
use Modules\UserManagement\Http\Requests\UpdateUserRequest;
use Modules\UserManagement\Http\Resources\UserResource;
use Modules\UserManagement\Models\User;

/**
 * Admin API Controller for managing users
 */
class UserController extends BaseApiController
{
    public function __construct(
        private CreateUserAction $createUserAction,
        private UpdateUserAction $updateUserAction,
        private DeleteUserAction $deleteUserAction,
        private ListUsersAction $listUsersAction
    ) {
    }

    /**
     * List all users
     */
    public function index(ListUsersRequest $request): JsonResponse
    {
        $this->authorize('viewAny', User::class);

        $dto = ListUsersDTO::fromArray($request->validated());
        $users = $this->listUsersAction->execute($dto);

        return $this->paginatedResource($users, UserResource::class, 'Users retrieved successfully');
    }

    /**
     * Show a specific user
     */
    public function show(User $user): JsonResponse
    {
        $this->authorize('view', $user);

        $user->load(['roles.permissions']);

        return $this->success(new UserResource($user), 'User retrieved successfully');
    }

    /**
     * Create a new user
     * 
     * If password is not provided, a set password email will be sent to the user.
     */
    public function store(StoreUserRequest $request): JsonResponse
    {
        $this->authorize('create', User::class);

        $validated = $request->validated();

        $dto = CreateUserDTO::fromArray($validated);
        $user = $this->createUserAction->execute($dto);

        $message = empty($validated['password']) 
            ? 'User created successfully. A set password email has been sent.'
            : 'User created successfully';

        return $this->success(new UserResource($user), $message, 201);
    }

    /**
     * Update a user
     */
    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        $this->authorize('update', $user);

        $dto = UpdateUserDTO::fromArray($request->validated());
        $user = $this->updateUserAction->execute($user, $dto);

        return $this->success(new UserResource($user), 'User updated successfully');
    }

    /**
     * Delete a user
     */
    public function destroy(User $user): JsonResponse
    {
        $this->authorize('delete', $user);

        try {
            $this->deleteUserAction->execute($user);
            return $this->success(null, 'User deleted successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 403);
        }
    }
}
