<?php

declare(strict_types=1);

namespace Modules\PaymentGateway\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Form Request for listing payments (admin)
 */
class ListAdminPaymentsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['sometimes', 'string', Rule::in(['pending', 'completed', 'failed', 'refunded', 'cancelled'])],
            'user_id' => ['sometimes', 'integer', 'exists:users,id'],
            'invoice_id' => ['sometimes', 'integer', 'exists:invoices,id'],
            'gateway_name' => ['sometimes', 'string', 'max:255'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'page' => ['sometimes', 'integer', 'min:1'],
        ];
    }
}
