<?php

declare(strict_types=1);

namespace Modules\Authentication\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource for login response
 */
class AuthLoginResource extends JsonResource
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
            'token' => $this->resource['token'] ?? null,
            'token_type' => $this->resource['token_type'] ?? 'Bearer',
            'user' => $this->resource['user'] ?? null,
            'requires_2fa' => $this->resource['requires_2fa'] ?? false,
            'two_factor_token' => $this->resource['two_factor_token'] ?? null,
        ];
    }
}
