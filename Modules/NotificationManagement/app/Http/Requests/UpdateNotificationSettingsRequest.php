<?php

declare(strict_types=1);

namespace Modules\NotificationManagement\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateNotificationSettingsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'pusher_app_id' => ['nullable', 'string', 'max:255'],
            'pusher_key' => ['nullable', 'string', 'max:255'],
            'pusher_secret' => ['nullable', 'string', 'max:255'],
            'pusher_cluster' => ['nullable', 'string', 'max:50'],
            'pusher_enabled' => ['nullable', 'boolean'],
        ];
    }
}
