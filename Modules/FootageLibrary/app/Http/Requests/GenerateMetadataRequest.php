<?php

declare(strict_types=1);

namespace Modules\FootageLibrary\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GenerateMetadataRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'metadata_source' => 'required|string|in:title,frames',
        ];
    }
}
