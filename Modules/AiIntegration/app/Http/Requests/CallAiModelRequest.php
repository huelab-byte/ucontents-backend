<?php

declare(strict_types=1);

namespace Modules\AiIntegration\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Form request for calling an AI model
 */
class CallAiModelRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('call_ai_models') ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'provider_slug' => 'required|string',
            'model' => 'required|string',
            'prompt' => 'required|string',
            'api_key_id' => 'nullable|exists:ai_api_keys,id',
            'settings' => 'nullable|array',
            'settings.temperature' => 'nullable|numeric|min:0|max:2',
            'settings.max_tokens' => 'nullable|integer|min:1|max:32000',
            'module' => 'nullable|string|max:255',
            'feature' => 'nullable|string|max:255',
            'metadata' => 'nullable|array',
        ];
    }
}
