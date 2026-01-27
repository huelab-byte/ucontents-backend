<?php

declare(strict_types=1);

namespace Modules\UserManagement\Http\Controllers\Api\V1\Customer;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\Http\Controllers\Api\BaseApiController;
use Modules\UserManagement\Actions\UpdateUserAction;
use Modules\UserManagement\DTOs\UpdateUserDTO;
use Modules\UserManagement\Http\Requests\UpdateProfileRequest;
use Modules\UserManagement\Http\Resources\UserResource;
use Modules\UserManagement\Models\User;

/**
 * Customer API Controller for profile management
 */
class ProfileController extends BaseApiController
{
    public function __construct(
        private UpdateUserAction $updateUserAction
    ) {
    }

    /**
     * Get current user's profile
     */
    public function show(Request $request): JsonResponse
    {
        $user = $request->user()->load(['roles.permissions']);

        return $this->success(new UserResource($user), 'Profile retrieved successfully');
    }

    /**
     * Update current user's profile
     */
    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        // Remove roles from update (customers can't change their own roles)
        unset($validated['roles']);

        $dto = UpdateUserDTO::fromArray($validated);
        $user = $this->updateUserAction->execute($user, $dto);

        return $this->success(new UserResource($user), 'Profile updated successfully');
    }
}
