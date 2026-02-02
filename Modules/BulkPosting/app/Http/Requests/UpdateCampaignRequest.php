<?php

declare(strict_types=1);

namespace Modules\BulkPosting\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCampaignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'brand_name' => ['sometimes', 'string', 'max:255'],
            'project_name' => ['sometimes', 'string', 'max:255'],
            'brand_logo_storage_file_id' => ['nullable', 'integer', 'exists:storage_files,id'],
            'content_source_type' => ['sometimes', 'string', 'in:csv_file,media_upload'],
            'content_source_config' => ['nullable', 'array'],
            'content_source_config.folder_ids' => ['nullable', 'array'],
            'content_source_config.folder_ids.*' => ['integer'],
            'content_source_config.csv_storage_file_id' => ['nullable', 'integer'],
            'schedule_condition' => ['sometimes', 'string', 'in:minute,hourly,daily,weekly,monthly'],
            'schedule_interval' => ['sometimes', 'integer', 'min:1'],
            'repost_enabled' => ['sometimes', 'boolean'],
            'repost_condition' => ['nullable', 'string', 'in:minute,hourly,daily,weekly,monthly'],
            'repost_interval' => ['nullable', 'integer', 'min:0'],
            'repost_max_count' => ['nullable', 'integer', 'min:1'],
            'connections' => ['sometimes', 'array'],
            'connections.channels' => ['nullable', 'array'],
            'connections.channels.*' => ['integer'],
            'connections.groups' => ['nullable', 'array'],
            'connections.groups.*' => ['integer'],
        ];
    }
}
