<?php

declare(strict_types=1);

namespace Modules\PlanManagement\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListPlansRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'is_active' => ['sometimes', 'boolean'],
            'subscription_type' => ['sometimes', 'string', Rule::in(['weekly', 'monthly', 'yearly', 'lifetime'])],
            'featured' => ['sometimes', 'boolean'],
            'is_free_plan' => ['sometimes', 'boolean'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'page' => ['sometimes', 'integer', 'min:1'],
        ];
    }
}
