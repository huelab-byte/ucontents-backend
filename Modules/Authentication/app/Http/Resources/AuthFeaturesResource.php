<?php

declare(strict_types=1);

namespace Modules\Authentication\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource for authentication features configuration
 */
class AuthFeaturesResource extends JsonResource
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
            'magic_link' => $this->resource['magic_link'] ?? [],
            'otp' => $this->resource['otp'] ?? [],
            'social_auth' => $this->resource['social_auth'] ?? [],
            'email_verification' => $this->resource['email_verification'] ?? [],
            'password_reset' => $this->resource['password_reset'] ?? [],
            'password' => $this->resource['password'] ?? [],
        ];
    }
}
