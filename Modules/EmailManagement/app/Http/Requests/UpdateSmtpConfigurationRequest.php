<?php

declare(strict_types=1);

namespace Modules\EmailManagement\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\EmailManagement\Models\SmtpConfiguration;

class UpdateSmtpConfigurationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization handled by middleware
    }

    public function rules(): array
    {
        $smtpConfig = $this->route('smtp_configuration');
        // Get ID from model instance (route model binding) or use the value directly
        $id = $smtpConfig instanceof SmtpConfiguration 
            ? $smtpConfig->id 
            : $smtpConfig;

        return [
            'name' => 'required|string|max:255|unique:smtp_configurations,name,' . $id,
            'host' => 'required|string|max:255',
            'port' => 'required|integer|min:1|max:65535',
            'encryption' => 'nullable|string|in:tls,ssl',
            'username' => 'required|string|max:255',
            'password' => 'nullable|string', // Optional on update
            'from_address' => 'required|email|max:255',
            'from_name' => 'nullable|string|max:255',
            'is_active' => 'nullable|boolean',
            'is_default' => 'nullable|boolean',
            'options' => 'nullable|array',
        ];
    }
}
