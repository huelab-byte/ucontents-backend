<?php

declare(strict_types=1);

namespace Modules\PaymentGateway\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateInvoiceRequest extends FormRequest
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
            'type' => 'required|string|in:package,subscription,one_time',
            'subtotal' => 'required|numeric|min:0',
            'tax' => 'nullable|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|size:3',
            'due_date' => 'nullable|date|after:today',
            'notes' => 'nullable|string|max:5000',
            'metadata' => 'nullable|array',
            'invoiceable_type' => 'nullable|string',
            'invoiceable_id' => 'nullable|integer',
        ];
    }
}
