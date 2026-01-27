<?php

declare(strict_types=1);

namespace Modules\Client\Http\Controllers\Api\V1\Admin;

use Illuminate\Http\JsonResponse;
use Modules\Client\Actions\GenerateApiKeyAction;
use Modules\Client\Actions\RevokeApiKeyAction;
use Modules\Client\Actions\RotateApiKeyAction;
use Modules\Client\DTOs\GenerateApiKeyDTO;
use Modules\Client\Http\Requests\RevokeApiKeyRequest;
use Modules\Client\Http\Requests\StoreApiKeyRequest;
use Modules\Client\Http\Requests\ListApiKeysRequest;
use Modules\Client\Http\Requests\ListApiKeyActivityLogsRequest;
use Modules\Client\Http\Resources\ApiKeyActivityLogResource;
use Modules\Client\Http\Resources\ApiKeyResource;
use Modules\Client\Models\ApiClient;
use Modules\Client\Models\ApiKey;
use Modules\Core\Http\Controllers\Api\BaseApiController;

/**
 * Admin API Controller for managing API keys
 */
class ApiKeyController extends BaseApiController
{
    public function __construct(
        private GenerateApiKeyAction $generateApiKeyAction,
        private RevokeApiKeyAction $revokeApiKeyAction,
        private RotateApiKeyAction $rotateApiKeyAction
    ) {
    }

    /**
     * List API keys for a client
     */
    public function index(ListApiKeysRequest $request, ApiClient $apiClient): JsonResponse
    {
        $this->authorize('viewAny', ApiKey::class);

        $query = $apiClient->apiKeys()
            ->with('apiClient')
            ->orderBy('created_at', 'desc');

        // Filter by active status
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $keys = $query->paginate($request->input('per_page', 15));

        return $this->paginatedResource($keys, ApiKeyResource::class, 'API keys retrieved successfully');
    }

    /**
     * Show a specific API key (without secret)
     */
    public function show(ApiKey $apiKey): JsonResponse
    {
        $this->authorize('view', $apiKey);

        $apiKey->load('apiClient');

        return $this->success(new ApiKeyResource($apiKey), 'API key retrieved successfully');
    }

    /**
     * Generate a new API key for a client
     */
    public function store(StoreApiKeyRequest $request, ApiClient $apiClient): JsonResponse
    {
        $this->authorize('create', ApiKey::class);

        $validated = $request->validated();
        $validated['api_client_id'] = $apiClient->id;
        
        $dto = GenerateApiKeyDTO::fromArray($validated);
        $result = $this->generateApiKeyAction->execute($dto);

        return $this->success($result, 'API key generated successfully', 201);
    }

    /**
     * Revoke an API key
     */
    public function revoke(RevokeApiKeyRequest $request, ApiKey $apiKey): JsonResponse
    {
        $this->authorize('revoke', $apiKey);

        $this->revokeApiKeyAction->execute($apiKey, $request->validated()['reason'] ?? null);

        return $this->success(null, 'API key revoked successfully');
    }

    /**
     * Rotate an API key
     */
    public function rotate(ApiKey $apiKey): JsonResponse
    {
        $this->authorize('rotate', $apiKey);

        $result = $this->rotateApiKeyAction->execute($apiKey);

        return $this->success($result, 'API key rotated successfully');
    }

    /**
     * Get activity logs for an API key
     */
    public function activityLogs(ListApiKeyActivityLogsRequest $request, ApiKey $apiKey): JsonResponse
    {
        $this->authorize('view', $apiKey);

        $query = $apiKey->activityLogs()
            ->orderBy('created_at', 'desc');

        // Filter by date range
        if ($request->has('from')) {
            $query->where('created_at', '>=', $request->input('from'));
        }
        if ($request->has('to')) {
            $query->where('created_at', '<=', $request->input('to'));
        }

        $logs = $query->paginate($request->input('per_page', 50));

        return $this->paginatedResource($logs, ApiKeyActivityLogResource::class, 'Activity logs retrieved successfully');
    }
}
