<?php

declare(strict_types=1);

namespace Modules\Client\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreApiClientRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'environment' => 'nullable|string|in:production,staging,development',
            'is_active' => 'nullable|boolean',
            'allowed_endpoints' => 'nullable|array',
            'rate_limit' => 'nullable|array',
            'rate_limit.limit' => 'nullable|integer|min:1',
            'rate_limit.period' => 'nullable|integer|min:1',
            'expires_at' => 'nullable|date|after:now',
        ];
    }
}
