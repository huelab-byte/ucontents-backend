<?php

declare(strict_types=1);

namespace Modules\Client\Actions;

use Modules\Client\Models\ApiClient;

/**
 * Action to delete an API client
 */
class DeleteApiClientAction
{
    public function execute(ApiClient $apiClient): bool
    {
        // Also revoke all associated API keys
        $apiClient->apiKeys()->delete();
        
        return $apiClient->delete();
    }
}
