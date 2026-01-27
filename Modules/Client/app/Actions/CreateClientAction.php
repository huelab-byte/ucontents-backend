<?php

declare(strict_types=1);

namespace Modules\Client\Actions;

use Modules\Client\DTOs\CreateClientDTO;
use Modules\Client\Models\ApiClient;

/**
 * Action to create a new API client
 */
class CreateClientAction
{
    public function execute(CreateClientDTO $dto, ?int $createdBy = null): ApiClient
    {
        $client = new ApiClient();
        $client->name = $dto->name;
        $client->description = $dto->description;
        $client->environment = $dto->environment;
        $client->allowed_endpoints = $dto->allowedEndpoints;
        $client->rate_limit = $dto->rateLimit;
        $client->expires_at = $dto->expiresAt;
        $client->created_by = $createdBy;
        $client->is_active = true;
        $client->save();

        return $client;
    }
}
