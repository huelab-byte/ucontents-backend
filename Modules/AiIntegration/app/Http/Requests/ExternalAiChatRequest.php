<?php

declare(strict_types=1);

namespace Modules\AiIntegration\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Form request for external AI chat
 */
class ExternalAiChatRequest extends FormRequest
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
            'message' => 'required|string|max:10000',
            'max_tokens' => 'nullable|integer|min:10|max:4000',
            'conversation_id' => 'nullable|string|max:255',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'message.required' => 'Please enter a message',
            'message.max' => 'Message is too long (max 10,000 characters)',
            'max_tokens.min' => 'Max tokens must be at least 10',
            'max_tokens.max' => 'Max tokens cannot exceed 4,000',
        ];
    }
}
