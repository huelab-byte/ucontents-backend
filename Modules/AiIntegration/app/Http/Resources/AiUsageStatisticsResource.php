<?php

declare(strict_types=1);

namespace Modules\AiIntegration\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource for AI usage statistics
 */
class AiUsageStatisticsResource extends JsonResource
{
    /**
     * The "data" wrapper that should be applied.
     */
    public static $wrap = null;

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'total_requests' => $this->resource['total_requests'] ?? 0,
            'total_tokens' => $this->resource['total_tokens'] ?? 0,
            'total_cost' => $this->resource['total_cost'] ?? 0,
            'successful_requests' => $this->resource['successful_requests'] ?? 0,
            'failed_requests' => $this->resource['failed_requests'] ?? 0,
            'by_provider' => $this->resource['by_provider'] ?? [],
            'by_model' => $this->resource['by_model'] ?? [],
        ];
    }
}
