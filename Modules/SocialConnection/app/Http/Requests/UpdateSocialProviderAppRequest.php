<?php

declare(strict_types=1);

namespace Modules\SocialConnection\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSocialProviderAppRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Authorization is enforced via route middleware + policies in controller.
        return true;
    }

    public function rules(): array
    {
        return [
            'enabled' => 'sometimes|boolean',
            'client_id' => 'nullable|string|max:255',
            'client_secret' => 'nullable|string|max:2000',
            'scopes' => 'nullable|array',
            'scopes.*' => 'string|max:500',
            'extra' => 'nullable|array',
        ];
    }
}

