<?php

declare(strict_types=1);

namespace Modules\Authentication\Http\Controllers\Api\V1\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use Modules\Authentication\Actions\UpdateAuthSettingsAction;
use Modules\Authentication\Http\Requests\UpdateAuthSettingsRequest;
use Modules\Core\Http\Controllers\Api\BaseApiController;

/**
 * Admin API Controller for managing authentication settings
 */
class AuthSettingsController extends BaseApiController
{
    public function __construct(
        private UpdateAuthSettingsAction $updateSettingsAction
    ) {}

    /**
     * Get current authentication settings
     */
    public function index(): JsonResponse
    {
        Gate::authorize('manage_auth_settings');

        return $this->success(
            $this->updateSettingsAction->getFormattedSettings(),
            'Authentication settings retrieved successfully'
        );
    }

    /**
     * Update authentication settings
     */
    public function update(UpdateAuthSettingsRequest $request): JsonResponse
    {
        Gate::authorize('manage_auth_settings');

        $settings = $this->updateSettingsAction->execute($request->validated());

        return $this->success($settings, 'Authentication settings updated successfully');
    }
}
