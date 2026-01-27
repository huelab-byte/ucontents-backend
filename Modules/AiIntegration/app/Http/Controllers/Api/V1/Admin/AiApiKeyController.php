<?php

declare(strict_types=1);

namespace Modules\AiIntegration\Http\Controllers\Api\V1\Admin;

use Illuminate\Http\JsonResponse;
use Modules\AiIntegration\Actions\CreateApiKeyAction;
use Modules\AiIntegration\Actions\DeleteApiKeyAction;
use Modules\AiIntegration\Actions\ToggleApiKeyAction;
use Modules\AiIntegration\Actions\UpdateApiKeyAction;
use Modules\AiIntegration\DTOs\CreateApiKeyDTO;
use Modules\AiIntegration\DTOs\UpdateApiKeyDTO;
use Modules\AiIntegration\Http\Requests\StoreApiKeyRequest;
use Modules\AiIntegration\Http\Requests\UpdateApiKeyRequest;
use Modules\AiIntegration\Http\Requests\ListAiApiKeysRequest;
use Modules\AiIntegration\Http\Resources\AiApiKeyResource;
use Modules\AiIntegration\Models\AiApiKey;
use Modules\Core\Http\Controllers\Api\BaseApiController;

/**
 * Admin API Controller for managing AI API keys
 */
class AiApiKeyController extends BaseApiController
{
    public function __construct(
        private CreateApiKeyAction $createApiKeyAction,
        private UpdateApiKeyAction $updateApiKeyAction,
        private ToggleApiKeyAction $toggleApiKeyAction,
        private DeleteApiKeyAction $deleteApiKeyAction
    ) {
    }

    /**
     * List all API keys
     */
    public function index(ListAiApiKeysRequest $request): JsonResponse
    {
        $this->authorize('viewAny', AiApiKey::class);

        $query = AiApiKey::with('provider');

        // Filter by provider
        if ($request->has('provider_id')) {
            $query->where('provider_id', $request->input('provider_id'));
        }

        // Filter by active status
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $apiKeys = $query->orderBy('priority', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate($request->input('per_page', 15));

        return $this->paginatedResource(
            $apiKeys,
            AiApiKeyResource::class,
            'API keys retrieved successfully'
        );
    }

    /**
     * Show a specific API key
     */
    public function show(AiApiKey $apiKey): JsonResponse
    {
        $this->authorize('view', $apiKey);

        $apiKey->load('provider');

        return $this->success(
            new AiApiKeyResource($apiKey),
            'API key retrieved successfully'
        );
    }

    /**
     * Create a new API key
     */
    public function store(StoreApiKeyRequest $request): JsonResponse
    {
        $this->authorize('create', AiApiKey::class);

        $dto = CreateApiKeyDTO::fromArray($request->validated());
        $apiKey = $this->createApiKeyAction->execute($dto);
        $apiKey->load('provider');

        return $this->success(
            new AiApiKeyResource($apiKey),
            'API key created successfully',
            201
        );
    }

    /**
     * Update an API key
     */
    public function update(UpdateApiKeyRequest $request, AiApiKey $apiKey): JsonResponse
    {
        $this->authorize('update', $apiKey);

        $dto = UpdateApiKeyDTO::fromArray($request->validated());
        $apiKey = $this->updateApiKeyAction->execute($apiKey, $dto);
        $apiKey->load('provider');

        return $this->success(
            new AiApiKeyResource($apiKey),
            'API key updated successfully'
        );
    }

    /**
     * Delete an API key
     */
    public function destroy(AiApiKey $apiKey): JsonResponse
    {
        $this->authorize('delete', $apiKey);

        $this->deleteApiKeyAction->execute($apiKey);

        return $this->success(
            null,
            'API key deleted successfully'
        );
    }

    /**
     * Enable an API key
     */
    public function enable(AiApiKey $apiKey): JsonResponse
    {
        $this->authorize('update', $apiKey);

        $apiKey = $this->toggleApiKeyAction->execute($apiKey, true);

        return $this->success(
            new AiApiKeyResource($apiKey),
            'API key enabled successfully'
        );
    }

    /**
     * Disable an API key
     */
    public function disable(AiApiKey $apiKey): JsonResponse
    {
        $this->authorize('update', $apiKey);

        $apiKey = $this->toggleApiKeyAction->execute($apiKey, false);

        return $this->success(
            new AiApiKeyResource($apiKey),
            'API key disabled successfully'
        );
    }
}
