<?php

declare(strict_types=1);

namespace Modules\ProxySetup\Http\Controllers\Api\V1\Customer;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Schema;
use Modules\Core\Http\Controllers\Api\BaseApiController;
use Modules\ProxySetup\Actions\UpdateProxySettingsAction;
use Modules\ProxySetup\DTOs\UpdateProxySettingsDTO;
use Modules\ProxySetup\Http\Requests\UpdateProxySettingsRequest;
use Modules\ProxySetup\Http\Resources\ProxySettingsResource;
use Modules\ProxySetup\Models\Proxy;
use Modules\ProxySetup\Models\ProxySetting;

class ProxySettingsController extends BaseApiController
{
    /**
     * Get proxy settings
     */
    public function show(): JsonResponse
    {
        $this->authorize('viewAny', Proxy::class);

        if (!Schema::hasTable('proxy_settings')) {
            return $this->error(
                "ProxySetup tables are not migrated yet. Run `php artisan migrate`.",
                500
            );
        }

        $settings = ProxySetting::getOrCreateForUser(request()->user()->id);

        return $this->success(new ProxySettingsResource($settings), 'Proxy settings retrieved successfully');
    }

    /**
     * Update proxy settings
     */
    public function update(
        UpdateProxySettingsRequest $request,
        UpdateProxySettingsAction $action
    ): JsonResponse {
        $this->authorize('create', Proxy::class);

        if (!Schema::hasTable('proxy_settings')) {
            return $this->error(
                "ProxySetup tables are not migrated yet. Run `php artisan migrate`.",
                500
            );
        }

        $dto = UpdateProxySettingsDTO::fromArray($request->validated());
        $settings = $action->execute($request->user(), $dto);

        return $this->success(new ProxySettingsResource($settings), 'Proxy settings updated successfully');
    }
}
