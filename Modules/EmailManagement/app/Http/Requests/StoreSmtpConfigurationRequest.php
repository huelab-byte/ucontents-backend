<?php

declare(strict_types=1);

namespace Modules\EmailManagement\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSmtpConfigurationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization handled by middleware
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255|unique:smtp_configurations,name',
            'host' => 'required|string|max:255',
            'port' => 'required|integer|min:1|max:65535',
            'encryption' => 'nullable|string|in:tls,ssl',
            'username' => 'required|string|max:255',
            'password' => 'required|string',
            'from_address' => 'required|email|max:255',
            'from_name' => 'nullable|string|max:255',
            'is_active' => 'nullable|boolean',
            'is_default' => 'nullable|boolean',
            'options' => 'nullable|array',
        ];
    }
}
