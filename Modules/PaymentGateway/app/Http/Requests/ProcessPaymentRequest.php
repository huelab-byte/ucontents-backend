<?php

declare(strict_types=1);

namespace Modules\PaymentGateway\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProcessPaymentRequest extends FormRequest
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
            'invoice_id' => 'required|exists:invoices,id',
            'payment_gateway_id' => 'nullable|exists:payment_gateways,id',
            'gateway_name' => 'nullable|string|in:stripe,paypal',
            'payment_method' => 'nullable|string|in:card,bank_transfer,paypal,other',
            'gateway_data' => 'nullable|array',
            'metadata' => 'nullable|array',
        ];
    }
}
