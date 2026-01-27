<?php

declare(strict_types=1);

namespace Modules\Client\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \Modules\Client\Models\ApiClient
 */
class ApiClientResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'is_active' => $this->is_active,
            'rate_limit' => $this->rate_limit,
            'rate_limit_period' => $this->rate_limit_period,
            'allowed_ips' => $this->allowed_ips,
            'allowed_origins' => $this->allowed_origins,
            'metadata' => $this->metadata,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'api_keys' => ApiKeyResource::collection($this->whenLoaded('apiKeys')),
            'api_keys_count' => $this->when(isset($this->api_keys_count), $this->api_keys_count),
        ];
    }
}
