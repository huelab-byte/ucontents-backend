<?php

declare(strict_types=1);

namespace Modules\AiIntegration\Actions;

use Illuminate\Support\Facades\Log;
use Modules\AiIntegration\Models\AiApiKey;
use Modules\AiIntegration\Adapters\AdapterFactory;

/**
 * Action to test if an API key is valid and working
 */
class TestApiKeyAction
{
    public function __construct(
        private AdapterFactory $adapterFactory
    ) {
    }

    /**
     * Test if an API key is valid by making a simple API call
     *
     * @param AiApiKey $apiKey
     * @return array{success: bool, message: string, response_time_ms?: int, error?: string}
     */
    public function execute(AiApiKey $apiKey): array
    {
        $startTime = microtime(true);

        try {
            $provider = $apiKey->provider;

            if (!$provider) {
                return [
                    'success' => false,
                    'message' => 'Provider not found for this API key',
                    'error' => 'Provider not found',
                ];
            }

            $adapter = $this->adapterFactory->create($provider);

            // Build configuration for the adapter
            $config = $this->buildConfig($apiKey, $provider);

            // Make a simple test call - use a minimal prompt. For Azure, use key's deployment name if set.
            $deploymentName = $apiKey->metadata['deployment_name'] ?? null;
            $testModel = ($provider->slug === 'azure_openai' && $deploymentName !== null && $deploymentName !== '')
                ? $deploymentName
                : $this->getDefaultModel($provider);
            $dto = new \Modules\AiIntegration\DTOs\AiModelCallDTO(
                providerSlug: $provider->slug,
                model: $testModel,
                prompt: 'Say "OK" in one word.',
                apiKeyId: $apiKey->id,
                settings: [
                    'max_tokens' => 10,
                    'temperature' => 0,
                ],
                metadata: [
                    'is_test' => true
                ]
            );

            try {
                $result = $adapter->callModel($apiKey, $dto);
            } catch (\Exception $e) {
                // Special handling for Google Gemini: if 1.5-flash is not found, try gemini-pro
                if (
                    $provider->slug === 'google' &&
                    $testModel === 'gemini-1.5-flash' &&
                    (str_contains($e->getMessage(), 'not found') || str_contains($e->getMessage(), '404'))
                ) {

                    $fallbackModel = 'gemini-pro';
                    $dto = new \Modules\AiIntegration\DTOs\AiModelCallDTO(
                        providerSlug: $provider->slug,
                        model: $fallbackModel,
                        prompt: 'Say "OK" in one word.',
                        apiKeyId: $apiKey->id,
                        settings: [
                            'max_tokens' => 10,
                            'temperature' => 0,
                        ],
                        metadata: [
                            'is_test' => true
                        ]
                    );

                    try {
                        $result = $adapter->callModel($apiKey, $dto);
                        $result['model'] = $fallbackModel;
                    } catch (\Exception $fallbackError) {
                        // Fallback also failed. Use the original error or a combined one.
                        // If it's a 404, we can say models unavailable. If it's a 400 (API_KEY_INVALID), say that.

                        $msg = $e->getMessage();

                        // If the first error was about API key validity, just re-throw that.
                        if (str_contains($msg, 'API key not valid') || str_contains($msg, 'API_KEY_INVALID')) {
                            throw $e;
                        }

                        throw new \Exception("Google Gemini models are unavailable. Tried 'gemini-1.5-flash' and 'gemini-pro'. Original error: " . $msg);
                    }
                } else {
                    throw $e;
                }
            }

            $responseTimeMs = (int) ((microtime(true) - $startTime) * 1000);

            if (isset($result['error'])) {
                return [
                    'success' => false,
                    'message' => 'API key test failed',
                    'error' => $result['error'],
                    'response_time_ms' => $responseTimeMs,
                ];
            }

            return [
                'success' => true,
                'message' => 'API key is valid and working',
                'response_time_ms' => $responseTimeMs,
                'model' => $result['model'] ?? $this->getDefaultModel($provider),
            ];
        } catch (\Exception $e) {
            $responseTimeMs = (int) ((microtime(true) - $startTime) * 1000);

            Log::warning('API key test failed', [
                'api_key_id' => $apiKey->id,
                'provider' => $apiKey->provider?->slug,
                'error' => $e->getMessage(),
            ]);

            // Parse common error messages for better UX
            $errorMessage = $this->parseErrorMessage($e->getMessage(), $apiKey);

            return [
                'success' => false,
                'message' => 'API key test failed',
                'error' => $errorMessage,
                'response_time_ms' => $responseTimeMs,
            ];
        }
    }

    /**
     * Build configuration for the adapter
     */
    private function buildConfig(AiApiKey $apiKey, $provider): array
    {
        $config = [
            'api_key' => $apiKey->getDecryptedApiKey(),
        ];

        if ($apiKey->endpoint_url) {
            $config['base_url'] = $apiKey->endpoint_url;
        } elseif ($provider->base_url) {
            $config['base_url'] = $provider->base_url;
        }

        if ($apiKey->organization_id) {
            $config['organization'] = $apiKey->organization_id;
        }

        if ($apiKey->project_id) {
            $config['project'] = $apiKey->project_id;
        }

        // For Azure OpenAI, we need the api_version
        if ($provider->slug === 'azure_openai') {
            $config['api_version'] = $provider->metadata['api_version'] ?? '2024-02-15-preview';
        }

        return $config;
    }

    /**
     * Get a default model for testing based on the provider
     */
    private function getDefaultModel($provider): string
    {
        return match ($provider->slug) {
            'openai' => 'gpt-3.5-turbo',
            'azure_openai' => 'gpt-35-turbo',
            'anthropic' => 'claude-3-haiku-20240307',
            'google' => 'gemini-1.5-flash',
            'deepseek' => 'deepseek-chat',
            'xai' => 'grok-beta',
            'ucontents' => 'mistral-7b-instruct',
            default => 'gpt-3.5-turbo',
        };
    }

    /**
     * Parse error messages for better UX
     */
    private function parseErrorMessage(string $message, AiApiKey $apiKey): string
    {
        // Common API key errors
        if (str_contains($message, 'Incorrect API key') || str_contains($message, 'invalid_api_key')) {
            return 'Invalid API key. Please check if the key is correct.';
        }

        if (str_contains($message, 'quota') || str_contains($message, 'rate_limit')) {
            return 'API rate limit or quota exceeded. The key may be valid but has reached its usage limits.';
        }

        if (str_contains($message, 'authentication') || str_contains($message, '401')) {
            return 'Authentication failed. Please verify your API key and credentials.';
        }

        if (str_contains($message, 'permission') || str_contains($message, '403')) {
            return 'Permission denied. The API key may not have access to the requested resource.';
        }

        // Check for specific model 404 errors
        if ((str_contains($message, 'model') || str_contains($message, 'Model')) && (str_contains($message, 'not found') || str_contains($message, '404'))) {
            return 'Model not found. The selected model ' . ($this->getDefaultModel($apiKey->provider) ?? '') . ' may not be available for your API key or region.';
        }

        if (str_contains($message, 'not found') || str_contains($message, '404')) {
            return 'Endpoint not found. Please check if the endpoint URL is correct.';
        }

        if (str_contains($message, 'timeout') || str_contains($message, 'timed out')) {
            return 'Request timed out. The API server may be slow or unreachable.';
        }

        if (str_contains($message, 'connection') || str_contains($message, 'Could not resolve host')) {
            return 'Connection error. Please check your network and endpoint URL.';
        }

        // Return original message if no pattern matches (truncated if too long)
        return strlen($message) > 200 ? substr($message, 0, 200) . '...' : $message;
    }
}
