<?php

declare(strict_types=1);

namespace Modules\Client\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \Modules\Client\Models\ApiKeyActivityLog
 */
class ApiKeyActivityLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'api_key_id' => $this->api_key_id,
            'api_client_id' => $this->api_client_id,
            'endpoint' => $this->endpoint,
            'method' => $this->method,
            'status_code' => $this->status_code,
            'ip_address' => $this->ip_address,
            'user_agent' => $this->user_agent,
            'request_data' => $this->request_data,
            'response_data' => $this->response_data,
            'response_time_ms' => $this->response_time_ms,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
