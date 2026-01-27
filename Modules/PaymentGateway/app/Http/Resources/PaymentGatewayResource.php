<?php

declare(strict_types=1);

namespace Modules\PaymentGateway\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentGatewayResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'display_name' => $this->display_name,
            'is_active' => $this->is_active,
            'is_test_mode' => $this->is_test_mode,
            'description' => $this->description,
            'is_ready' => $this->isReady(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
