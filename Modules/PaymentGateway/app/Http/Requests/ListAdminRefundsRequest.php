<?php

declare(strict_types=1);

namespace Modules\PaymentGateway\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Form Request for listing refunds (admin)
 */
class ListAdminRefundsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['sometimes', 'string', Rule::in(['pending', 'approved', 'rejected', 'completed', 'failed'])],
            'user_id' => ['sometimes', 'integer', 'exists:users,id'],
            'payment_id' => ['sometimes', 'integer', 'exists:payments,id'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'page' => ['sometimes', 'integer', 'min:1'],
        ];
    }
}
