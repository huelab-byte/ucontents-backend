<?php

declare(strict_types=1);

namespace Modules\AiIntegration\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Core\Traits\AuthorizesWithSuperAdmin;

/**
 * Form request for storing an AI API key
 */
class StoreApiKeyRequest extends FormRequest
{
    use AuthorizesWithSuperAdmin;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->hasPermission('manage_ai_api_keys');
    }

    /**
     * Get the validation rules that apply to the request.
     */
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
            'priority' => 'sometimes|integer|min:0',
            'rate_limit_per_minute' => 'nullable|integer|min:1',
            'rate_limit_per_day' => 'nullable|integer|min:1',
            'metadata' => 'nullable|array',
        ];
    }
}
