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
        $apiKey = $this->getApiKey($dto);
        
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
     * Get API key for the request
     */
    private function getApiKey(AiModelCallDTO $dto): ?AiApiKey
    {
        if ($dto->apiKeyId) {
            return $this->apiKeyService->getBestApiKey($dto->providerSlug, $dto->apiKeyId);
        }

        return $this->apiKeyService->getBestApiKey($dto->providerSlug);
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
            'model' => $dto->model,
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

        // Pricing per 1M tokens (approximate)
        $pricing = [
            'openai' => [
                'gpt-4o' => 5.0,
                'gpt-4o-mini' => 0.15,
                'gpt-4-turbo' => 10.0,
                'gpt-4' => 30.0,
                'gpt-3.5-turbo' => 0.5,
            ],
            'anthropic' => [
                'claude-3-5-sonnet-20241022' => 3.0,
                'claude-3-opus-20240229' => 15.0,
                'claude-3-sonnet-20240229' => 3.0,
                'claude-3-haiku-20240307' => 0.25,
            ],
            'google' => [
                'gemini-1.5-pro' => 1.25,
                'gemini-1.5-flash' => 0.075,
                'gemini-pro' => 0.5,
                'gemini-pro-vision' => 0.5,
            ],
            'deepseek' => ['deepseek-chat' => 0.14, 'deepseek-coder' => 0.14],
            'xai' => ['grok-beta' => 0.5, 'grok-2' => 0.5, 'grok-vision-beta' => 0.5],
        ];

        $model = $response['model'] ?? '';
        $tokens = $response['total_tokens'] ?? 0;

        $costPerMillion = $pricing[$providerSlug][$model] ?? null;

        if ($costPerMillion === null) {
            return null;
        }

        return ($tokens / 1_000_000) * $costPerMillion;
    }
}
