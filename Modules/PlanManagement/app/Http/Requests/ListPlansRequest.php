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

    protected function prepareForValidation(): void
    {
        $merge = [];
        foreach (['is_active', 'featured', 'is_free_plan'] as $key) {
            if ($this->has($key)) {
                $value = $this->input($key);
                if (is_string($value)) {
                    $merge[$key] = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? $value;
                }
            }
        }
        if ($merge !== []) {
            $this->merge($merge);
        }
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
