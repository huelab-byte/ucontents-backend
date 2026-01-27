<?php

declare(strict_types=1);

namespace Modules\AiIntegration\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * API Resource for AI Usage Log
 */
class AiUsageLogResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'provider_slug' => $this->provider_slug,
            'model' => $this->model,
            'prompt' => $this->when($request->user()?->can('view_ai_usage'), $this->prompt),
            'response' => $this->when($request->user()?->can('view_ai_usage'), $this->response),
            'prompt_tokens' => $this->prompt_tokens,
            'completion_tokens' => $this->completion_tokens,
            'total_tokens' => $this->total_tokens,
            'cost' => $this->cost,
            'response_time_ms' => $this->response_time_ms,
            'status' => $this->status,
            'error_message' => $this->error_message,
            'module' => $this->module,
            'feature' => $this->feature,
            'user' => $this->whenLoaded('user'),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
