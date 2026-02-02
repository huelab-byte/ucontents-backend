<?php

declare(strict_types=1);

namespace Modules\PlanManagement\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $plan = $this->route('plan');

        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'slug' => ['sometimes', 'string', 'max:100', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('plans', 'slug')->ignore($plan?->id)],
            'description' => ['sometimes', 'nullable', 'string'],
            'ai_usage_limit' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'max_file_upload' => ['sometimes', 'integer', 'min:0'],
            'total_storage_bytes' => ['sometimes', 'integer', 'min:0'],
            'features' => ['sometimes', 'nullable', 'array'],
            'features.*' => ['string', 'max:100'],
            'max_connections' => ['sometimes', 'integer', 'min:0'],
            'monthly_post_limit' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'subscription_type' => ['sometimes', 'string', Rule::in(['weekly', 'monthly', 'yearly', 'lifetime'])],
            'price' => ['sometimes', 'numeric', 'min:0'],
            'currency' => ['sometimes', 'string', 'size:3'],
            'is_active' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
            'featured' => ['sometimes', 'boolean'],
            'is_free_plan' => ['sometimes', 'boolean'],
            'trial_days' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:365'],
        ];
    }
}
