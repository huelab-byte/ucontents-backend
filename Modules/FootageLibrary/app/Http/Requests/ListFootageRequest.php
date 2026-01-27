<?php

declare(strict_types=1);

namespace Modules\FootageLibrary\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Form Request for listing footage
 */
class ListFootageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => ['sometimes', 'integer', 'exists:users,id'],
            'folder_id' => ['sometimes', 'nullable', 'integer'],
            'status' => ['sometimes', 'string', 'in:pending,processing,completed,failed'],
            // Orientation can be a string or an array of strings
            'orientation' => ['sometimes'],
            'orientation.*' => ['sometimes', 'string', 'in:horizontal,vertical'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'page' => ['sometimes', 'integer', 'min:1'],
        ];
    }

    /**
     * Prepare data for validation - normalize orientation to array
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('orientation') && is_string($this->orientation)) {
            $this->merge([
                'orientation' => [$this->orientation],
            ]);
        }
    }
}
