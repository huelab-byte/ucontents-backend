<?php

declare(strict_types=1);

namespace Modules\SocialConnection\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SaveSelectedChannelsRequest extends FormRequest
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
            'selected_channels' => 'required|array|min:1',
            'selected_channels.*' => 'required|string',
        ];
    }

    public function messages(): array
    {
        return [
            'selected_channels.required' => 'Please select at least one channel.',
            'selected_channels.min' => 'Please select at least one channel.',
        ];
    }
}
