<?php

declare(strict_types=1);

namespace Modules\PaymentGateway\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Form Request for listing subscriptions (admin)
 */
class ListAdminSubscriptionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['sometimes', 'string', Rule::in(['active', 'paused', 'cancelled', 'expired'])],
            'interval' => ['sometimes', 'string', Rule::in(['monthly', 'yearly', 'weekly'])],
            'user_id' => ['sometimes', 'integer', 'exists:users,id'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'page' => ['sometimes', 'integer', 'min:1'],
        ];
    }
}
