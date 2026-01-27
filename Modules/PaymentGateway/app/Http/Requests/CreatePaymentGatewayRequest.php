<?php

declare(strict_types=1);

namespace Modules\PaymentGateway\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreatePaymentGatewayRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Policy handles authorization
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

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $isActive = $this->boolean('is_active', false);
            $isTestMode = $this->boolean('is_test_mode', false);

            if ($isActive && $isTestMode) {
                $validator->errors()->add(
                    'is_active',
                    'A payment gateway cannot be active and in test mode at the same time. Please disable test mode to activate the gateway.'
                );
            }
        });
    }
}
