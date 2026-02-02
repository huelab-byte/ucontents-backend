<?php

declare(strict_types=1);

namespace Modules\SocialConnection\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ConnectProviderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'channel_types' => ['sometimes', 'array'],
            'channel_types.*' => ['string', 'in:facebook_page,facebook_profile,instagram_business'],
            'callback_base_url' => ['sometimes', 'string', 'max:500'],
        ];
    }
}
