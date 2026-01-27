<?php

declare(strict_types=1);

namespace Modules\Support\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReplySupportTicketRequest extends FormRequest
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
            'message' => ['nullable', 'string'],
            'attachments' => ['sometimes', 'array'],
            'attachments.*' => ['integer', 'exists:storage_files,id'],
            'is_internal' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $message = $this->input('message');
            $attachments = $this->input('attachments', []);

            // Require at least a message or attachments
            if (empty($message) && empty($attachments)) {
                $validator->errors()->add('message', 'Either a message or attachments are required.');
            }
        });
    }
}
