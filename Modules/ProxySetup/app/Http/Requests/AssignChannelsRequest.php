<?php

declare(strict_types=1);

namespace Modules\ProxySetup\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AssignChannelsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'channel_ids' => ['required', 'array'],
            'channel_ids.*' => ['required', 'integer', 'exists:social_connection_channels,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'channel_ids.required' => 'At least one channel must be specified.',
            'channel_ids.array' => 'Channel IDs must be provided as an array.',
            'channel_ids.*.exists' => 'One or more selected channels do not exist.',
        ];
    }
}
