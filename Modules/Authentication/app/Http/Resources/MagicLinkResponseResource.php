<?php

declare(strict_types=1);

namespace Modules\Authentication\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource for magic link request response
 */
class MagicLinkResponseResource extends JsonResource
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
            'message' => $this->resource['message'] ?? 'Magic link sent to your email',
            'expires_at' => $this->resource['expires_at'] ?? null,
        ];
    }
}
