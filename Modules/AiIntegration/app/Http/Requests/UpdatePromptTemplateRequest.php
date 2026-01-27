<?php

declare(strict_types=1);

namespace Modules\AiIntegration\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Form request for updating a prompt template
 */
class UpdatePromptTemplateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('manage_prompt_templates') ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $templateId = $this->route('prompt_template')?->id;

        return [
            'name' => 'sometimes|string|max:255',
            'slug' => [
                'sometimes',
                'string',
                'max:255',
                Rule::unique('ai_prompt_templates', 'slug')->ignore($templateId),
            ],
            'template' => 'sometimes|string',
            'description' => 'nullable|string',
            'variables' => 'nullable|array',
            'category' => 'nullable|string|max:255',
            'provider_slug' => 'nullable|string',
            'model' => 'nullable|string',
            'settings' => 'nullable|array',
            'is_active' => 'sometimes|boolean',
        ];
    }
}
