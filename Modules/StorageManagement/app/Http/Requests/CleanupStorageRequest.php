<?php

declare(strict_types=1);

namespace Modules\StorageManagement\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CleanupStorageRequest extends FormRequest
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
            'older_than_days' => 'nullable|integer|min:1|max:365',
        ];
    }
}
