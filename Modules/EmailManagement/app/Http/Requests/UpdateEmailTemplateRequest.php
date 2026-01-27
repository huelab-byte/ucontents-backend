<?php

declare(strict_types=1);

namespace Modules\EmailManagement\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\EmailManagement\Models\EmailTemplate;

class UpdateEmailTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization handled by middleware
    }

    public function rules(): array
    {
        $emailTemplate = $this->route('email_template');
        // Get ID from model instance (route model binding) or use the value directly
        $id = $emailTemplate instanceof EmailTemplate 
            ? $emailTemplate->id 
            : $emailTemplate;

        return [
            'name' => 'required|string|max:255|unique:email_templates,name,' . $id,
            'slug' => 'nullable|string|max:255|unique:email_templates,slug,' . $id,
            'subject' => 'required|string|max:500',
            'body_html' => 'required|string',
            'body_text' => 'nullable|string',
            'variables' => 'nullable|array',
            'category' => 'nullable|string|max:255',
            'is_active' => 'nullable|boolean',
        ];
    }
}
