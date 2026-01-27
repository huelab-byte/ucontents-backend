<?php

declare(strict_types=1);

namespace Modules\Client\Http\Controllers\Api\V1\Admin;

use Illuminate\Http\JsonResponse;
use Modules\Client\Actions\CreateClientAction;
use Modules\Client\Actions\DeleteApiClientAction;
use Modules\Client\Actions\UpdateClientAction;
use Modules\Client\DTOs\CreateClientDTO;
use Modules\Client\DTOs\UpdateClientDTO;
use Modules\Client\Http\Requests\StoreApiClientRequest;
use Modules\Client\Http\Requests\UpdateApiClientRequest;
use Modules\Client\Http\Requests\ListApiClientsRequest;
use Modules\Client\Http\Resources\ApiClientResource;
use Modules\Client\Models\ApiClient;
use Modules\Core\Http\Controllers\Api\BaseApiController;

/**
 * Admin API Controller for managing API clients
 */
class ApiClientController extends BaseApiController
{
    public function __construct(
        private CreateClientAction $createClientAction,
        private UpdateClientAction $updateClientAction,
        private DeleteApiClientAction $deleteApiClientAction
    ) {
    }

    /**
     * List all API clients
     */
    public function index(ListApiClientsRequest $request): JsonResponse
    {
        $this->authorize('viewAny', ApiClient::class);

        $query = ApiClient::with(['creator', 'apiKeys'])
            ->withCount('apiKeys');

        // Filter by environment
        if ($request->has('environment')) {
            $query->where('environment', $request->input('environment'));
        }

        // Filter by active status
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        // Search by name
        if ($request->has('search')) {
            $query->where('name', 'like', '%' . $request->input('search') . '%');
        }

        $clients = $query->paginate($request->input('per_page', 15));

        return $this->paginatedResource($clients, ApiClientResource::class, 'API clients retrieved successfully');
    }

    /**
     * Show a specific API client
     */
    public function show(ApiClient $apiClient): JsonResponse
    {
        $this->authorize('view', $apiClient);

        $apiClient->load(['creator', 'apiKeys', 'activeApiKeys']);

        return $this->success(new ApiClientResource($apiClient), 'API client retrieved successfully');
    }

    /**
     * Create a new API client
     */
    public function store(StoreApiClientRequest $request): JsonResponse
    {
        $this->authorize('create', ApiClient::class);

        $dto = CreateClientDTO::fromArray($request->validated());
        $client = $this->createClientAction->execute($dto, $request->user()?->id);

        return $this->success(new ApiClientResource($client), 'API client created successfully', 201);
    }

    /**
     * Update an API client
     */
    public function update(UpdateApiClientRequest $request, ApiClient $apiClient): JsonResponse
    {
        $this->authorize('update', $apiClient);

        $dto = UpdateClientDTO::fromArray($request->validated());
        $client = $this->updateClientAction->execute($apiClient, $dto);

        return $this->success(new ApiClientResource($client), 'API client updated successfully');
    }

    /**
     * Delete an API client
     */
    public function destroy(ApiClient $apiClient): JsonResponse
    {
        $this->authorize('delete', $apiClient);

        $this->deleteApiClientAction->execute($apiClient);

        return $this->success(null, 'API client deleted successfully');
    }
}
