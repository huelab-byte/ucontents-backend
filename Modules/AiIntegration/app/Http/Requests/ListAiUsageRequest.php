<?php

declare(strict_types=1);

namespace Modules\AiIntegration\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Core\Traits\AuthorizesWithSuperAdmin;

/**
 * Form request for listing AI usage logs with filters
 */
class ListAiUsageRequest extends FormRequest
{
    use AuthorizesWithSuperAdmin;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->hasPermission('view_ai_usage');
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'provider_slug' => 'sometimes|string|max:100',
            'user_id' => 'sometimes|integer|exists:users,id',
            'status' => 'sometimes|string|in:success,error,pending',
            'date_from' => 'sometimes|date|date_format:Y-m-d',
            'date_to' => 'sometimes|date|date_format:Y-m-d|after_or_equal:date_from',
            'per_page' => 'sometimes|integer|min:1|max:100',
            'page' => 'sometimes|integer|min:1',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'date_to.after_or_equal' => 'The end date must be after or equal to the start date.',
            'status.in' => 'The status must be one of: success, error, pending.',
        ];
    }
}
