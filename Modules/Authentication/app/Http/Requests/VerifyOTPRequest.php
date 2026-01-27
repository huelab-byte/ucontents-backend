<?php

declare(strict_types=1);

namespace Modules\Authentication\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VerifyOTPRequest extends FormRequest
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
            'code' => 'required|string|size:6',
            'email' => 'nullable|string|email',
            'user_id' => 'nullable|integer|exists:users,id',
            'type' => 'nullable|string|in:login,verification,password_reset',
        ];
    }
}
