<?php

declare(strict_types=1);

namespace Modules\SocialConnection\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BulkAssignGroupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'channel_ids' => 'required|array',
            'channel_ids.*' => 'required|integer|exists:social_connection_channels,id',
            'group_id' => 'nullable|integer|exists:social_connection_groups,id',
        ];
    }
}
