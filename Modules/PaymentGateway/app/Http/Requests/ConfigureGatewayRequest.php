<?php

declare(strict_types=1);

namespace Modules\PaymentGateway\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ConfigureGatewayRequest extends FormRequest
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
            'name' => 'required|string|in:stripe,paypal',
            'display_name' => 'required|string|max:255',
            'is_active' => 'nullable|boolean',
            'is_test_mode' => 'nullable|boolean',
            'credentials' => 'required|array',
            'settings' => 'nullable|array',
            'description' => 'nullable|string|max:1000',
        ];
    }
}
