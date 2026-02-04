<?php

declare(strict_types=1);

namespace Modules\AiIntegration\Http\Controllers\Api\V1\Customer;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\AiIntegration\Actions\CreateApiKeyAction;
use Modules\AiIntegration\Actions\DeleteApiKeyAction;
use Modules\AiIntegration\Actions\TestApiKeyAction;
use Modules\AiIntegration\Actions\ToggleApiKeyAction;
use Modules\AiIntegration\Actions\UpdateApiKeyAction;
use Modules\AiIntegration\DTOs\CreateApiKeyDTO;
use Modules\AiIntegration\DTOs\UpdateApiKeyDTO;
use Modules\AiIntegration\Http\Requests\StoreCustomerApiKeyRequest;
use Modules\AiIntegration\Http\Requests\UpdateCustomerApiKeyRequest;
use Modules\AiIntegration\Http\Resources\AiApiKeyResource;
use Modules\AiIntegration\Models\AiApiKey;
use Modules\Core\Http\Controllers\Api\BaseApiController;

/**
 * Customer API Controller for managing their own AI API keys
 */
class AiApiKeyController extends BaseApiController
{
    public function __construct(
        private CreateApiKeyAction $createApiKeyAction,
        private UpdateApiKeyAction $updateApiKeyAction,
        private ToggleApiKeyAction $toggleApiKeyAction,
        private DeleteApiKeyAction $deleteApiKeyAction,
        private TestApiKeyAction $testApiKeyAction
    ) {
    }

    /**
     * List customer's API keys
     */
    public function index(Request $request): JsonResponse
    {
        // No permission check needed, just auth (handled by middleware)
        
        $query = AiApiKey::with('provider')
            ->where('user_id', auth()->id());

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
            'Your API keys retrieved successfully'
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
     * Create a new API key for the customer
     */
    public function store(StoreCustomerApiKeyRequest $request): JsonResponse
    {
        // Policy check for create (allows all customers)
        $this->authorize('create', AiApiKey::class);

        $data = $request->validated();
        $data['user_id'] = auth()->id(); // Force user ownership

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
     * Update an API key
     */
    public function update(UpdateCustomerApiKeyRequest $request, AiApiKey $apiKey): JsonResponse
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
     * Test an API key
     */
    public function test(AiApiKey $apiKey): JsonResponse
    {
        $this->authorize('update', $apiKey); // Must be owner

        $result = $this->testApiKeyAction->execute($apiKey);

        if ($result['success']) {
            return $this->success($result, $result['message']);
        }

        return $this->error($result['error'] ?? 'Test failed', 422, $result);
    }
    
    /**
     * Enable/Disable handled by update, but can add specifics if needed. 
     * Skipping specific endpoints for brevity, update(is_active) covers it.
     */
    /**
     * Get available AI providers
     */
    public function providers(): JsonResponse
    {
        $providers = \Modules\AiIntegration\Models\AiProvider::where('is_active', true)->get();
        return $this->success(
            \Modules\AiIntegration\Http\Resources\AiProviderResource::collection($providers),
            'Providers retrieved successfully'
        );
    }

    /**
     * Get available scopes
     */
    public function scopes(): JsonResponse
    {
        $scopes = config('aiintegration.module.scopes', []);
        $scopeList = [];
        foreach ($scopes as $slug => $config) {
            $scopeList[] = [
                'slug' => $slug,
                'name' => $config['name'] ?? ucfirst($slug),
                'description' => $config['description'] ?? '',
            ];
        }
        return $this->success($scopeList, 'Available scopes retrieved successfully');
    }
}
