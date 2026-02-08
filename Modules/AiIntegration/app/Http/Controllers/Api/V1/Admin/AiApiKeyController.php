<?php

declare(strict_types=1);

namespace Modules\AiIntegration\Http\Controllers\Api\V1\Admin;

use Illuminate\Http\JsonResponse;
use Modules\AiIntegration\Actions\CreateApiKeyAction;
use Modules\AiIntegration\Actions\DeleteApiKeyAction;
use Modules\AiIntegration\Actions\TestApiKeyAction;
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
        private DeleteApiKeyAction $deleteApiKeyAction,
        private TestApiKeyAction $testApiKeyAction,
        private \Modules\AiIntegration\Services\AiApiKeyQueryService $queryService
    ) {
    }

    /**
     * List all API keys
     */
    public function index(ListAiApiKeysRequest $request): JsonResponse
    {
        $this->authorize('viewAny', AiApiKey::class);

        $filters = $request->only(['provider_id', 'is_active']);
        $apiKeys = $this->queryService->listAllWithFilters(
            $filters,
            (int) $request->input('per_page', 15)
        );

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
     * Create a new API key (always stored as system key so customers without their own key can use it).
     */
    public function store(StoreApiKeyRequest $request): JsonResponse
    {
        $this->authorize('create', AiApiKey::class);

        $data = array_merge($request->validated(), ['user_id' => null]);
        $dto = CreateApiKeyDTO::fromArray($data);
        $apiKey = $this->createApiKeyAction->execute($dto);
        $apiKey->load('provider');

        return $this->success(
            new AiApiKeyResource($apiKey),
            'API key created successfully',
            201
        );
    }

    /**
     * Update an API key (ensure admin-managed keys stay as system keys).
     */
    public function update(UpdateApiKeyRequest $request, AiApiKey $apiKey): JsonResponse
    {
        $this->authorize('update', $apiKey);

        $dto = UpdateApiKeyDTO::fromArray($request->validated());
        $apiKey = $this->updateApiKeyAction->execute($apiKey, $dto);
        $apiKey->update(['user_id' => null]);
        $apiKey = $apiKey->fresh();
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

    /**
     * Test an API key by making a simple API call
     */
    public function test(AiApiKey $apiKey): JsonResponse
    {
        $this->authorize('update', $apiKey);

        $result = $this->testApiKeyAction->execute($apiKey);

        if ($result['success']) {
            return $this->success($result, $result['message']);
        }

        return $this->error($result['error'] ?? 'Test failed', 422, $result);
    }

    /**
     * Get available scopes for API key configuration
     */
    public function scopes(): JsonResponse
    {
        $this->authorize('viewAny', AiApiKey::class);

        $scopes = config('aiintegration.module.scopes', []);

        // Transform scopes into a more frontend-friendly format
        $scopeList = [];
        foreach ($scopes as $slug => $config) {
            $scopeList[] = [
                'slug' => $slug,
                'name' => $config['name'] ?? ucfirst($slug),
                'description' => $config['description'] ?? '',
                'module' => $config['module'] ?? null,
                'requires_vision' => $config['requires_vision'] ?? false,
                'requires_embedding_model' => $config['requires_embedding_model'] ?? false,
            ];
        }

        return $this->success($scopeList, 'Available scopes retrieved successfully');
    }
}

