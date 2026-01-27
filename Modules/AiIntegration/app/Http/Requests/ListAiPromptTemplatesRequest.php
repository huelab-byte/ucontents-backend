<?php

declare(strict_types=1);

namespace Modules\AiIntegration\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Form Request for listing AI prompt templates
 */
class ListAiPromptTemplatesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category' => ['sometimes', 'string', 'max:100'],
            'provider_slug' => ['sometimes', 'string', 'max:100'],
            'is_active' => ['sometimes', 'boolean'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'page' => ['sometimes', 'integer', 'min:1'],
        ];
    }
}
