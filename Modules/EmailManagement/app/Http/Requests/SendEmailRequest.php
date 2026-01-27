<?php

declare(strict_types=1);

namespace Modules\EmailManagement\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SendEmailRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization handled by middleware
    }

    public function rules(): array
    {
        return [
            'to' => 'required|email|max:255',
            'cc' => 'nullable|email|max:255',
            'bcc' => 'nullable|email|max:255',
            'subject' => 'required|string|max:500',
            'body' => 'required|string',
            'template_id' => 'nullable|integer|exists:email_templates,id',
            'template_variables' => 'nullable|array',
            'smtp_configuration_id' => 'nullable|integer|exists:smtp_configurations,id',
            'use_queue' => 'nullable|boolean',
            'metadata' => 'nullable|array',
        ];
    }
}
