<?php

declare(strict_types=1);

namespace Modules\FootageLibrary\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SearchFootageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search_text' => 'required|string|min:10',
            'content_length' => 'required|numeric|min:1',
            'folder_id' => 'nullable|integer|exists:footage_folders,id',
            'orientation' => 'nullable|string|in:horizontal,vertical',
            'footage_length' => 'nullable|numeric|min:0.1',
        ];
    }
}
