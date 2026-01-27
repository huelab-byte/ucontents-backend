<?php

declare(strict_types=1);

namespace Modules\NotificationManagement\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Form Request for listing announcements
 */
class ListAnnouncementsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'page' => ['sometimes', 'integer', 'min:1'],
        ];
    }
}
