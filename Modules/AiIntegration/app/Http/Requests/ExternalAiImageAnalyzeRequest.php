<?php

declare(strict_types=1);

namespace Modules\AiIntegration\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Form request for external AI image analysis
 */
class ExternalAiImageAnalyzeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('use_ai_chat') ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'message' => 'nullable|string|max:10000',
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:10240', // 10MB max
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'image.required' => 'Please upload an image',
            'image.image' => 'The file must be an image',
            'image.mimes' => 'Image must be a JPEG, PNG, GIF, or WebP file',
            'image.max' => 'Image size cannot exceed 10MB',
            'message.max' => 'Message is too long (max 10,000 characters)',
        ];
    }
}
