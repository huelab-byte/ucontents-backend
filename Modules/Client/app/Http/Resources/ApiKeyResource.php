<?php

declare(strict_types=1);

namespace Modules\Client\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \Modules\Client\Models\ApiKey
 */
class ApiKeyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'api_client_id' => $this->api_client_id,
            'name' => $this->name,
            'public_key' => $this->public_key,
            'is_active' => $this->is_active,
            'last_used_at' => $this->last_used_at?->toISOString(),
            'expires_at' => $this->expires_at?->toISOString(),
            'rotated_at' => $this->rotated_at?->toISOString(),
            'revoked_at' => $this->revoked_at?->toISOString(),
            'revoked_reason' => $this->revoked_reason,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'api_client' => $this->whenLoaded('apiClient', fn() => new ApiClientResource($this->apiClient)),
        ];
    }
}
