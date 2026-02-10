<?php

declare(strict_types=1);

namespace Modules\AiIntegration\Services;

use Illuminate\Support\Facades\Log;
use Modules\AiIntegration\Adapters\AdapterFactory;
use Modules\AiIntegration\DTOs\AiModelCallDTO;
use Modules\AiIntegration\Models\AiApiKey;
use Modules\AiIntegration\Models\AiUsageLog;

/**
 * Service for calling AI models using provider SDKs
 */
class AiModelCallService
{
    public function __construct(
        private AiApiKeyService $apiKeyService,
        private AdapterFactory $adapterFactory
    ) {
    }

    /**
     * Call an AI model
     */
    public function callModel(AiModelCallDTO $dto, ?int $userId = null): array
    {
        $startTime = microtime(true);

        // Get API key
        $apiKey = $this->getApiKey($dto, $userId);

        if (!$apiKey) {
            throw new \Exception("No available API key for provider: {$dto->providerSlug}");
        }

        $provider = $apiKey->provider;

        try {
            // Get adapter for the provider
            $adapter = $this->adapterFactory->create($provider);

            // Call the model using the provider's SDK
            $response = $adapter->callModel($apiKey, $dto);

            $responseTime = (int) ((microtime(true) - $startTime) * 1000);

            // Log usage
            $this->logUsage($apiKey, $dto, $response, $responseTime, AiUsageLog::STATUS_SUCCESS, $userId);

            // Mark API key as used
            $apiKey->markAsUsed($response['total_tokens'] ?? 0);

            return $response;

        } catch (\Exception $e) {
            $responseTime = (int) ((microtime(true) - $startTime) * 1000);

            // Log error
            $this->logUsage(
                $apiKey,
                $dto,
                null,
                $responseTime,
                AiUsageLog::STATUS_ERROR,
                $userId,
                $e->getMessage()
            );

            throw $e;
        }
    }

    /**
     * Get API key for the request using scope-based selection
     * 
     * Priority:
     * 1. If apiKeyId is specified, use that key (if it supports the scope)
     * 2. If scope is specified, find a key that supports that scope
     * 3. Fall back to any available key for the provider
     */
    private function getApiKey(AiModelCallDTO $dto, ?int $userId = null): ?AiApiKey
    {
        // Use scope-based selection
        return $this->apiKeyService->getBestApiKeyForScope(
            $dto->providerSlug,
            $dto->scope,
            $dto->apiKeyId,
            $userId
        );
    }

    /**
     * Log AI usage
     */
    private function logUsage(
        AiApiKey $apiKey,
        AiModelCallDTO $dto,
        ?array $response,
        int $responseTimeMs,
        string $status,
        ?int $userId,
        ?string $errorMessage = null
    ): void {
        AiUsageLog::create([
            'api_key_id' => $apiKey->id,
            'user_id' => $userId,
            'provider_slug' => $dto->providerSlug,
            'model' => $response['model'] ?? $dto->model,
            'prompt' => $dto->prompt,
            'response' => $response['content'] ?? null,
            'prompt_tokens' => $response['prompt_tokens'] ?? 0,
            'completion_tokens' => $response['completion_tokens'] ?? 0,
            'total_tokens' => $response['total_tokens'] ?? 0,
            'cost' => $this->calculateCost($dto->providerSlug, $response),
            'response_time_ms' => $responseTimeMs,
            'status' => $status,
            'error_message' => $errorMessage,
            'module' => $dto->module,
            'feature' => $dto->feature,
            'metadata' => $dto->metadata,
        ]);
    }

    /**
     * Calculate cost based on provider and usage
     */
    private function calculateCost(string $providerSlug, ?array $response): ?float
    {
        if (!$response || !isset($response['total_tokens'])) {
            return null;
        }

        // Pricing per 1M tokens (approximate blended input/output rate)
        $pricing = [
            'openai' => [
                'gpt-4o' => 5.0,
                'gpt-4o-mini' => 0.15,
                'gpt-4-turbo' => 10.0,
                'gpt-4-vision-preview' => 10.0,
                'gpt-4' => 30.0,
                'gpt-3.5-turbo' => 0.5,
                'gpt-3.5-turbo-instruct' => 1.5,
                'text-embedding-ada-002' => 0.10,
                'dall-e-3' => 40.0, // Per 1000 images approx, treated as tokens here is hard
            ],
            'azure_openai' => [
                'gpt-4o' => 5.0,
                'gpt-35-turbo' => 0.5,
            ],
            'anthropic' => [
                'claude-3-5-sonnet' => 3.0,
                'claude-3-opus' => 15.0,
                'claude-3-sonnet' => 3.0,
                'claude-3-haiku' => 0.25,
            ],
            'google' => [
                'gemini-1.5-pro' => 3.5,
                'gemini-1.5-flash' => 0.075,
                'gemini-pro' => 0.5,
                'gemini-pro-vision' => 0.5,
            ],
            'deepseek' => [
                'deepseek-chat' => 0.14,
                'deepseek-coder' => 0.14
            ],
            'xai' => [
                'grok-beta' => 5.0,
                'grok-vision-beta' => 5.0
            ],
            'ucontents' => [
                'mistral-7b-instruct' => 0.0,
                'moondream2' => 0.0
            ], // Self-hosted
        ];

        $model = $response['model'] ?? '';
        $tokens = $response['total_tokens'] ?? 0;

        $providerPricing = $pricing[$providerSlug] ?? [];
        $costPerMillion = $providerPricing[$model] ?? null;

        // Try fuzzy matching if exact match not found (e.g. gpt-4-0613 -> gpt-4)
        if ($costPerMillion === null) {
            foreach ($providerPricing as $key => $price) {
                if (str_starts_with($model, $key)) {
                    $costPerMillion = $price;
                    break;
                }
            }
        }

        if ($costPerMillion === null) {
            return null;
        }

        return ($tokens / 1_000_000) * $costPerMillion;
    }
}
