<?php

declare(strict_types=1);

namespace Modules\SocialConnection\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSocialConnectionGroupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
        ];
    }
}
