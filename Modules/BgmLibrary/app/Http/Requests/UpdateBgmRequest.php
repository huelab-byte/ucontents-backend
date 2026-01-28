<?php

declare(strict_types=1);

namespace Modules\BgmLibrary\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBgmRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'sometimes|string|max:255',
            'folder_id' => 'sometimes|nullable|integer|exists:bgm_folders,id',
            'metadata' => 'sometimes|array',
        ];
    }
}
