<?php

declare(strict_types=1);

namespace Modules\SocialConnection\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GetAvailableChannelsRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Authorization is enforced via route middleware + policies in controller.
        return true;
    }

    public function rules(): array
    {
        return [
            'token' => 'required|string|min:32|max:64',
        ];
    }
}
