<?php

declare(strict_types=1);

namespace Modules\Client\Actions;

use Modules\Client\Models\ApiKey;
use Modules\Client\Services\ApiKeyService;

/**
 * Action to rotate an API key
 */
class RotateApiKeyAction
{
    public function __construct(
        private ApiKeyService $apiKeyService
    ) {
    }

    public function execute(ApiKey $apiKey): array
    {
        if (!$apiKey->isActive()) {
            throw new \Exception('Cannot rotate inactive or revoked API key.');
        }

        $newApiKey = $this->apiKeyService->rotateApiKey($apiKey);

        // Return new key pair (only shown once)
        return [
            'old_api_key_id' => $apiKey->id,
            'new_api_key' => $newApiKey,
            'public_key' => $newApiKey->public_key,
            'secret_key' => $newApiKey->secret_key, // Only shown on rotation
        ];
    }
}
