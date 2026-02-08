<?php

declare(strict_types=1);

namespace Modules\AiIntegration\Adapters;

use OpenAI;
use Modules\AiIntegration\DTOs\AiModelCallDTO;
use Modules\AiIntegration\Models\AiApiKey;

/**
 * OpenAI and OpenAI-compatible providers adapter (OpenAI, Azure OpenAI, DeepSeek, xAI)
 */
class OpenAiAdapter implements ProviderAdapterInterface
{
    /**
     * Embedding models that should use the embeddings API
     */
    private const EMBEDDING_MODELS = [
        'text-embedding-ada-002',
        'text-embedding-3-small',
        'text-embedding-3-large',
    ];

    public function callModel(AiApiKey $apiKey, AiModelCallDTO $dto): array
    {
        $isAzure = $apiKey->provider?->slug === 'azure_openai';
        $baseUrl = $apiKey->endpoint_url ?? $apiKey->provider->base_url ?? 'https://api.openai.com/v1';
        $baseUrl = $this->normalizeAzureEndpoint($baseUrl, $apiKey);

        if ($isAzure) {
            // Azure: URL must be .../openai/deployments/{deployment-name}; client appends /chat/completions
            $deploymentName = $apiKey->metadata['deployment_name'] ?? $dto->model;
            $baseUrl = rtrim($baseUrl, '/') . '/deployments/' . $deploymentName;
        }

        $factory = OpenAI::factory()
            ->withApiKey($apiKey->getDecryptedApiKey())
            ->withBaseUri($baseUrl);

        if ($isAzure) {
            $factory->withHttpHeader('api-key', $apiKey->getDecryptedApiKey());
            $apiVersion = $apiKey->metadata['api_version'] ?? $apiKey->provider?->config['api_version'] ?? '2024-02-15-preview';
            $factory->withQueryParam('api-version', $apiVersion);
        }

        if ($apiKey->organization_id && ! $isAzure) {
            $factory->withOrganization($apiKey->organization_id);
        }

        $client = $factory->make();

        // For Azure, chat/embedding must use the deployment name in the request (from key metadata or dto model)
        $effectiveModel = $isAzure
            ? ($apiKey->metadata['deployment_name'] ?? $dto->model)
            : $dto->model;

        // Check if this is an embedding model
        if ($this->isEmbeddingModel($dto->model)) {
            return $this->callEmbeddingModel($client, $dto, $effectiveModel);
        }

        return $this->callChatModel($client, $dto, $effectiveModel);
    }

    /**
     * Normalize Azure OpenAI endpoint: Azure uses /openai/v1/... so base URL must end with /openai.
     */
    private function normalizeAzureEndpoint(string $baseUrl, AiApiKey $apiKey): string
    {
        $isAzure = $apiKey->provider?->slug === 'azure_openai';
        if (! $isAzure) {
            return $baseUrl;
        }
        $trimmed = rtrim($baseUrl, '/');
        if (str_contains($trimmed, 'openai.azure.com') && ! str_ends_with($trimmed, '/openai')) {
            return $trimmed . '/openai';
        }
        return $baseUrl;
    }

    /**
     * Check if the model is an embedding model
     */
    private function isEmbeddingModel(string $model): bool
    {
        return in_array($model, self::EMBEDDING_MODELS, true) 
            || str_starts_with($model, 'text-embedding-');
    }

    /**
     * Call the embeddings API
     * @param string $effectiveModel For Azure this is the deployment name; otherwise same as dto->model
     */
    private function callEmbeddingModel($client, AiModelCallDTO $dto, string $effectiveModel): array
    {
        $response = $client->embeddings()->create([
            'model' => $effectiveModel,
            'input' => $dto->prompt,
        ]);

        $embedding = $response->embeddings[0]->embedding ?? [];
        $usage = $response->usage;

        return [
            'embedding' => $embedding,
            'data' => [['embedding' => $embedding]], // Also include in data format for compatibility
            'prompt_tokens' => $usage->promptTokens ?? 0,
            'completion_tokens' => 0,
            'total_tokens' => $usage->totalTokens ?? $usage->promptTokens ?? 0,
            'model' => $response->model ?? $dto->model,
        ];
    }

    /**
     * Call the chat completions API
     * @param string $effectiveModel For Azure this is the deployment name; otherwise same as dto->model
     */
    private function callChatModel($client, AiModelCallDTO $dto, string $effectiveModel): array
    {
        $settings = array_merge([
            'temperature' => 0.7,
            'max_tokens' => 1000,
        ], $dto->settings ?? []);

        // Build message content - support vision with images
        $messageContent = $this->buildMessageContent($dto);

        $response = $client->chat()->create([
            'model' => $effectiveModel,
            'messages' => [
                ['role' => 'user', 'content' => $messageContent],
            ],
            'temperature' => $settings['temperature'],
            'max_tokens' => $settings['max_tokens'],
        ]);

        $choice = $response->choices[0] ?? null;
        $usage = $response->usage;

        return [
            'content' => $choice->message->content ?? '',
            'prompt_tokens' => $usage->promptTokens ?? 0,
            'completion_tokens' => $usage->completionTokens ?? 0,
            'total_tokens' => $usage->totalTokens ?? 0,
            'model' => $response->model ?? $dto->model,
            'finish_reason' => $choice->finishReason ?? null,
        ];
    }

    /**
     * Build message content, supporting vision with images
     */
    private function buildMessageContent(AiModelCallDTO $dto): string|array
    {
        // Check if there's an image in metadata
        $image = $dto->metadata['image'] ?? null;
        $imageFormat = $dto->metadata['image_format'] ?? 'base64';
        
        if (!$image) {
            // No image, return plain text
            return $dto->prompt;
        }

        // Build vision-compatible message with image
        $content = [
            [
                'type' => 'text',
                'text' => $dto->prompt,
            ],
        ];

        if ($imageFormat === 'base64') {
            $content[] = [
                'type' => 'image_url',
                'image_url' => [
                    'url' => 'data:image/jpeg;base64,' . $image,
                ],
            ];
        } elseif ($imageFormat === 'url') {
            $content[] = [
                'type' => 'image_url',
                'image_url' => [
                    'url' => $image,
                ],
            ];
        }

        return $content;
    }
}
