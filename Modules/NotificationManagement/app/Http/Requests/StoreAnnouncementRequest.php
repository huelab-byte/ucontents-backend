<?php

declare(strict_types=1);

namespace Modules\NotificationManagement\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Core\Traits\AuthorizesWithSuperAdmin;

class StoreAnnouncementRequest extends FormRequest
{
    use AuthorizesWithSuperAdmin;

    public function authorize(): bool
    {
        return $this->hasPermission('manage_announcements');
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'send_in_app' => ['sometimes', 'boolean'],
            'send_email' => ['sometimes', 'boolean'],
            'audience' => ['required', 'string', 'in:all_admins,specific_users'],
            'user_ids' => ['sometimes', 'array'],
            'user_ids.*' => ['integer', 'exists:users,id'],
            'severity' => ['sometimes', 'nullable', 'string', 'in:info,success,warning,error'],
            'data' => ['sometimes', 'nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'user_ids.*.exists' => 'One or more selected users do not exist.',
        ];
    }
}

