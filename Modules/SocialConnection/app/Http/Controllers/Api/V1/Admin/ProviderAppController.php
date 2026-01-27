<?php

declare(strict_types=1);

namespace Modules\SocialConnection\Http\Controllers\Api\V1\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Schema;
use Modules\Core\Http\Controllers\Api\BaseApiController;
use Modules\SocialConnection\Actions\DisableProviderAppAction;
use Modules\SocialConnection\Actions\EnableProviderAppAction;
use Modules\SocialConnection\Actions\FindOrCreateProviderAppAction;
use Modules\SocialConnection\Actions\UpdateProviderAppAction;
use Modules\SocialConnection\Http\Requests\UpdateSocialProviderAppRequest;
use Modules\SocialConnection\Http\Resources\SocialProviderAppResource;
use Modules\SocialConnection\Models\SocialProviderApp;

class ProviderAppController extends BaseApiController
{
    public function __construct(
        private FindOrCreateProviderAppAction $findOrCreateProviderAppAction,
        private UpdateProviderAppAction $updateProviderAppAction,
        private EnableProviderAppAction $enableProviderAppAction,
        private DisableProviderAppAction $disableProviderAppAction
    ) {
    }

    public function index(): JsonResponse
    {
        if (!Schema::hasTable('social_provider_apps')) {
            return $this->error(
                "SocialConnection tables are not migrated yet. Run `php artisan migrate`.",
                500
            );
        }

        $this->authorize('viewAny', SocialProviderApp::class);

        // Ensure provider rows exist for UI convenience
        $this->findOrCreateProviderAppAction->ensureAllProvidersExist();

        $apps = SocialProviderApp::query()->orderBy('provider')->get();

        return $this->success(SocialProviderAppResource::collection($apps), 'Providers retrieved successfully');
    }

    public function show(string $provider): JsonResponse
    {
        if (!Schema::hasTable('social_provider_apps')) {
            return $this->error(
                "SocialConnection tables are not migrated yet. Run `php artisan migrate`.",
                500
            );
        }

        $app = $this->findOrCreateProviderAppAction->execute($provider);
        $this->authorize('view', $app);

        return $this->success(new SocialProviderAppResource($app), 'Provider retrieved successfully');
    }

    public function update(UpdateSocialProviderAppRequest $request, string $provider): JsonResponse
    {
        if (!Schema::hasTable('social_provider_apps')) {
            return $this->error(
                "SocialConnection tables are not migrated yet. Run `php artisan migrate`.",
                500
            );
        }

        $app = $this->findOrCreateProviderAppAction->execute($provider);
        $this->authorize('update', $app);

        $app = $this->updateProviderAppAction->execute($app, $request->validated());

        return $this->success(new SocialProviderAppResource($app), 'Provider updated successfully');
    }

    public function enable(string $provider): JsonResponse
    {
        if (!Schema::hasTable('social_provider_apps')) {
            return $this->error(
                "SocialConnection tables are not migrated yet. Run `php artisan migrate`.",
                500
            );
        }

        $app = $this->findOrCreateProviderAppAction->execute($provider);
        $this->authorize('update', $app);

        $app = $this->enableProviderAppAction->execute($app);

        return $this->success(new SocialProviderAppResource($app), 'Provider enabled successfully');
    }

    public function disable(string $provider): JsonResponse
    {
        if (!Schema::hasTable('social_provider_apps')) {
            return $this->error(
                "SocialConnection tables are not migrated yet. Run `php artisan migrate`.",
                500
            );
        }

        $app = $this->findOrCreateProviderAppAction->execute($provider);
        $this->authorize('update', $app);

        $app = $this->disableProviderAppAction->execute($app);

        return $this->success(new SocialProviderAppResource($app), 'Provider disabled successfully');
    }
}

