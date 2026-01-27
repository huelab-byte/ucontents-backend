<?php

declare(strict_types=1);

namespace Modules\PaymentGateway\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Form Request for listing invoices (admin)
 */
class ListAdminInvoicesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['sometimes', 'string', 'in:draft,pending,paid,partially_paid,overdue,cancelled,refunded'],
            'type' => ['sometimes', 'string', 'in:one_time,subscription'],
            'user_id' => ['sometimes', 'integer', 'exists:users,id'],
            'search' => ['sometimes', 'string', 'max:255'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'page' => ['sometimes', 'integer', 'min:1'],
        ];
    }
}
