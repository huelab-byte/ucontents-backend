<?php

declare(strict_types=1);

namespace Modules\EmailManagement\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SendTestEmailRequest extends FormRequest
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
            'to' => 'required|email',
            'subject' => 'nullable|string|max:255',
            'body' => 'nullable|string',
            'smtp_configuration_id' => 'nullable|exists:smtp_configurations,id',
        ];
    }
}
