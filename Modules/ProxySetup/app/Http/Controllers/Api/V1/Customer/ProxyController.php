<?php

declare(strict_types=1);

namespace Modules\ProxySetup\Http\Controllers\Api\V1\Customer;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Schema;
use Modules\Core\Http\Controllers\Api\BaseApiController;
use Modules\ProxySetup\Actions\AssignChannelsToProxyAction;
use Modules\ProxySetup\Actions\CreateProxyAction;
use Modules\ProxySetup\Actions\DeleteProxyAction;
use Modules\ProxySetup\Actions\DisableProxyAction;
use Modules\ProxySetup\Actions\EnableProxyAction;
use Modules\ProxySetup\Actions\TestProxyConnectionAction;
use Modules\ProxySetup\Actions\UpdateProxyAction;
use Modules\ProxySetup\DTOs\CreateProxyDTO;
use Modules\ProxySetup\DTOs\UpdateProxyDTO;
use Modules\ProxySetup\Http\Requests\AssignChannelsRequest;
use Modules\ProxySetup\Http\Requests\StoreProxyRequest;
use Modules\ProxySetup\Http\Requests\UpdateProxyRequest;
use Modules\ProxySetup\Http\Resources\ProxyResource;
use Modules\ProxySetup\Http\Resources\ProxyWithChannelsResource;
use Modules\ProxySetup\Models\Proxy;

class ProxyController extends BaseApiController
{
    /**
     * List user's proxies
     */
    public function index(): JsonResponse
    {
        $this->authorize('viewAny', Proxy::class);

        if (!Schema::hasTable('proxies')) {
            return $this->error(
                "ProxySetup tables are not migrated yet. Run `php artisan migrate`.",
                500
            );
        }

        $proxies = Proxy::query()
            ->where('user_id', request()->user()->id)
            ->withCount('channels')
            ->orderBy('name')
            ->get();

        return $this->success(ProxyResource::collection($proxies), 'Proxies retrieved successfully');
    }

    /**
     * Create a new proxy
     */
    public function store(
        StoreProxyRequest $request,
        CreateProxyAction $action
    ): JsonResponse {
        $this->authorize('create', Proxy::class);

        if (!Schema::hasTable('proxies')) {
            return $this->error(
                "ProxySetup tables are not migrated yet. Run `php artisan migrate`.",
                500
            );
        }

        $dto = CreateProxyDTO::fromArray($request->validated());
        $proxy = $action->execute($request->user(), $dto);

        return $this->success(new ProxyResource($proxy), 'Proxy created successfully', 201);
    }

    /**
     * Get proxy details with assigned channels
     */
    public function show(int $id): JsonResponse
    {
        if (!Schema::hasTable('proxies')) {
            return $this->error(
                "ProxySetup tables are not migrated yet. Run `php artisan migrate`.",
                500
            );
        }

        $proxy = Proxy::with('channels')->findOrFail($id);
        $this->authorize('view', $proxy);

        return $this->success(new ProxyWithChannelsResource($proxy), 'Proxy retrieved successfully');
    }

    /**
     * Update a proxy
     */
    public function update(
        UpdateProxyRequest $request,
        int $id,
        UpdateProxyAction $action
    ): JsonResponse {
        if (!Schema::hasTable('proxies')) {
            return $this->error(
                "ProxySetup tables are not migrated yet. Run `php artisan migrate`.",
                500
            );
        }

        $proxy = Proxy::findOrFail($id);
        $this->authorize('update', $proxy);

        $dto = UpdateProxyDTO::fromArray($request->validated());
        $updated = $action->execute($proxy, $dto);

        return $this->success(new ProxyResource($updated), 'Proxy updated successfully');
    }

    /**
     * Delete a proxy
     */
    public function destroy(int $id, DeleteProxyAction $action): JsonResponse
    {
        if (!Schema::hasTable('proxies')) {
            return $this->error(
                "ProxySetup tables are not migrated yet. Run `php artisan migrate`.",
                500
            );
        }

        $proxy = Proxy::findOrFail($id);
        $this->authorize('delete', $proxy);

        $action->execute($proxy);

        return $this->success(null, 'Proxy deleted successfully');
    }

    /**
     * Enable a proxy
     */
    public function enable(int $id, EnableProxyAction $action): JsonResponse
    {
        if (!Schema::hasTable('proxies')) {
            return $this->error(
                "ProxySetup tables are not migrated yet. Run `php artisan migrate`.",
                500
            );
        }

        $proxy = Proxy::findOrFail($id);
        $this->authorize('update', $proxy);

        $updated = $action->execute($proxy);

        return $this->success(new ProxyResource($updated), 'Proxy enabled successfully');
    }

    /**
     * Disable a proxy
     */
    public function disable(int $id, DisableProxyAction $action): JsonResponse
    {
        if (!Schema::hasTable('proxies')) {
            return $this->error(
                "ProxySetup tables are not migrated yet. Run `php artisan migrate`.",
                500
            );
        }

        $proxy = Proxy::findOrFail($id);
        $this->authorize('update', $proxy);

        $updated = $action->execute($proxy);

        return $this->success(new ProxyResource($updated), 'Proxy disabled successfully');
    }

    /**
     * Test proxy connection
     */
    public function test(int $id, TestProxyConnectionAction $action): JsonResponse
    {
        if (!Schema::hasTable('proxies')) {
            return $this->error(
                "ProxySetup tables are not migrated yet. Run `php artisan migrate`.",
                500
            );
        }

        $proxy = Proxy::findOrFail($id);
        $this->authorize('view', $proxy);

        $result = $action->execute($proxy);

        $message = $result['success'] ? 'Proxy test successful' : 'Proxy test failed';

        return $this->success($result, $message);
    }

    /**
     * Assign channels to a proxy
     */
    public function assignChannels(
        AssignChannelsRequest $request,
        int $id,
        AssignChannelsToProxyAction $action
    ): JsonResponse {
        if (!Schema::hasTable('proxies') || !Schema::hasTable('proxy_channel_assignments')) {
            return $this->error(
                "ProxySetup tables are not migrated yet. Run `php artisan migrate`.",
                500
            );
        }

        $proxy = Proxy::findOrFail($id);
        $this->authorize('update', $proxy);

        $updated = $action->execute($proxy, $request->validated()['channel_ids']);

        return $this->success(new ProxyWithChannelsResource($updated), 'Channels assigned successfully');
    }
}
