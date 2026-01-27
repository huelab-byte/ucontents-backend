<?php

declare(strict_types=1);

namespace Modules\PaymentGateway\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateSubscriptionRequest extends FormRequest
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
            'user_id' => 'required|exists:users,id',
            'name' => 'required|string|max:255',
            'interval' => 'required|string|in:weekly,monthly,yearly',
            'amount' => 'required|numeric|min:0',
            'currency' => 'nullable|string|size:3',
            'payment_gateway_id' => 'nullable|exists:payment_gateways,id',
            'subscriptionable_type' => 'nullable|string',
            'subscriptionable_id' => 'nullable|integer',
            'start_date' => 'nullable|date|after_or_equal:today',
            'gateway_data' => 'nullable|array',
            'metadata' => 'nullable|array',
        ];
    }
}
