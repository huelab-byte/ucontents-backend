<?php

declare(strict_types=1);

namespace Modules\Client\Actions;

use Modules\Client\DTOs\UpdateClientDTO;
use Modules\Client\Models\ApiClient;

/**
 * Action to update an API client
 */
class UpdateClientAction
{
    public function execute(ApiClient $client, UpdateClientDTO $dto): ApiClient
    {
        $updateData = $dto->toArray();
        
        if (!empty($updateData)) {
            $client->update($updateData);
        }

        return $client->fresh();
    }
}
