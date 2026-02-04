<?php

declare(strict_types=1);

namespace Modules\AiIntegration\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCustomerApiKeyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization handled by Policy/Controller
    }

    public function rules(): array
    {
        return [
            'provider_id' => 'required|exists:ai_providers,id',
            'name' => 'required|string|max:255',
            'api_key' => 'required|string',
            'api_secret' => 'nullable|string',
            'endpoint_url' => 'nullable|url',
            'organization_id' => 'nullable|string|max:255',
            'project_id' => 'nullable|string|max:255',
            'is_active' => 'sometimes|boolean',
            // Allow basic fields, maybe restrict priority/limits?
            // Customers probably shouldn't set high priority over system keys?
            // But within their own keys, priority matters.
            'priority' => 'sometimes|integer|min:0',
            'rate_limit_per_minute' => 'nullable|integer|min:1',
            'rate_limit_per_day' => 'nullable|integer|min:1',
            'metadata' => 'nullable|array',
            'scopes' => 'nullable|array',
            'scopes.*' => 'string', // Validate scopes against config if possible
        ];
    }
}
