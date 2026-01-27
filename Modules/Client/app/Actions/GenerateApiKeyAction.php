<?php

declare(strict_types=1);

namespace Modules\Client\Actions;

use Modules\Client\DTOs\GenerateApiKeyDTO;
use Modules\Client\Models\ApiClient;
use Modules\Client\Services\ApiKeyService;

/**
 * Action to generate a new API key for a client
 */
class GenerateApiKeyAction
{
    public function __construct(
        private ApiKeyService $apiKeyService
    ) {
    }

    public function execute(GenerateApiKeyDTO $dto): array
    {
        $client = ApiClient::findOrFail($dto->apiClientId);

        if (!$client->isActive()) {
            throw new \Exception('Cannot generate API key for inactive or expired client.');
        }

        $apiKey = $this->apiKeyService->createApiKey(
            apiClientId: $dto->apiClientId,
            environment: $client->environment,
            name: $dto->name,
            expiresAt: $dto->expiresAt
        );

        // Return key pair (only shown once)
        return [
            'api_key' => $apiKey,
            'public_key' => $apiKey->public_key,
            'secret_key' => $apiKey->secret_key, // Only shown on creation
        ];
    }
}
