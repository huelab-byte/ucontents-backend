<?php

declare(strict_types=1);

namespace Modules\AiIntegration\Http\Controllers\Api\V1\Admin;

use Illuminate\Http\JsonResponse;
use Modules\AiIntegration\Http\Resources\AiProviderResource;
use Modules\AiIntegration\Models\AiProvider;
use Modules\AiIntegration\Services\AiProviderService;
use Modules\Core\Http\Controllers\Api\BaseApiController;

/**
 * Admin API Controller for managing AI providers
 */
class AiProviderController extends BaseApiController
{
    public function __construct(
        private AiProviderService $providerService
    ) {
    }

    /**
     * List all AI providers
     */
    public function index(): JsonResponse
    {
        $this->authorize('viewAny', AiProvider::class);

        $providers = AiProvider::withCount('activeApiKeys')->get();

        return $this->success(
            AiProviderResource::collection($providers),
            'AI providers retrieved successfully'
        );
    }

    /**
     * Show a specific provider
     */
    public function show(AiProvider $provider): JsonResponse
    {
        $this->authorize('view', $provider);

        $provider->loadCount('activeApiKeys');

        return $this->success(
            new AiProviderResource($provider),
            'AI provider retrieved successfully'
        );
    }

    /**
     * Initialize providers from config
     */
    public function initialize(): JsonResponse
    {
        $this->authorize('viewAny', AiProvider::class);

        $this->providerService->initializeProviders();

        return $this->success(
            null,
            'AI providers initialized successfully'
        );
    }
}
