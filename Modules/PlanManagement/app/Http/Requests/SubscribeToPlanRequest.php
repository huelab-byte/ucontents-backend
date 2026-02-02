<?php

declare(strict_types=1);

namespace Modules\PlanManagement\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubscribeToPlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'gateway_name' => ['sometimes', 'string', 'in:stripe,paypal'],
            'gateway_data' => ['sometimes', 'array'],
        ];
    }
}
