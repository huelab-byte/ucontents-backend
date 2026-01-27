<?php

declare(strict_types=1);

namespace Modules\GeneralSettings\Http\Controllers\Api\V1\Admin;

use Illuminate\Http\JsonResponse;
use Modules\GeneralSettings\Actions\UpdateGeneralSettingsAction;
use Modules\GeneralSettings\Http\Requests\UpdateGeneralSettingsRequest;
use Modules\Core\Http\Controllers\Api\BaseApiController;

/**
 * Admin API Controller for managing general settings
 */
class GeneralSettingsController extends BaseApiController
{
    public function __construct(
        private UpdateGeneralSettingsAction $updateSettingsAction
    ) {}

    /**
     * Get current general settings
     */
    public function index(): JsonResponse
    {
        $this->authorize('manage_general_settings');

        return $this->success(
            $this->updateSettingsAction->getFormattedSettings(),
            'General settings retrieved successfully'
        );
    }

    /**
     * Update general settings
     */
    public function update(UpdateGeneralSettingsRequest $request): JsonResponse
    {
        $this->authorize('manage_general_settings');

        $settings = $this->updateSettingsAction->execute($request->validated());

        return $this->success($settings, 'General settings updated successfully');
    }
}
