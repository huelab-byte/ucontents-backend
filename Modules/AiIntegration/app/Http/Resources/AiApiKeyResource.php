<?php

declare(strict_types=1);

namespace Modules\AiIntegration\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * API Resource for AI API Key
 */
class AiApiKeyResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'provider' => new AiProviderResource($this->whenLoaded('provider')),
            'provider_id' => $this->provider_id,
            'name' => $this->name,
            'api_key_preview' => $this->when(
                $request->user()?->can('manage_ai_api_keys'),
                substr($this->getDecryptedApiKey(), 0, 8) . '...' // Show only first 8 chars for preview
            ),
            'endpoint_url' => $this->endpoint_url,
            'organization_id' => $this->organization_id,
            'project_id' => $this->project_id,
            'is_active' => $this->is_active,
            'priority' => $this->priority,
            'rate_limit_per_minute' => $this->rate_limit_per_minute,
            'rate_limit_per_day' => $this->rate_limit_per_day,
            'last_used_at' => $this->last_used_at?->toISOString(),
            'total_requests' => $this->total_requests,
            'total_tokens' => $this->total_tokens,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
