<?php

declare(strict_types=1);

namespace Modules\PlanManagement\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AssignPlanToUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer', 'exists:users,id'],
        ];
    }
}
