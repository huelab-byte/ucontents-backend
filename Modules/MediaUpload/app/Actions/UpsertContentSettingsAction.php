<?php

declare(strict_types=1);

namespace Modules\MediaUpload\Actions;

use Modules\MediaUpload\Models\MediaUploadFolder;
use Modules\MediaUpload\Models\MediaUploadContentSettings;

class UpsertContentSettingsAction
{
    public function execute(MediaUploadFolder $folder, array $data): MediaUploadContentSettings
    {
        $settings = $folder->contentSettings;

        $defaults = [
            'content_source_type' => 'title',
            'heading_length' => 10,
            'heading_emoji' => false,
            'caption_length' => 30,
            'hashtag_count' => 3,
            'default_caption_template_id' => null,
            'default_loop_count' => 1,
            'default_enable_reverse' => false,
        ];

        $payload = [
            'content_source_type' => $data['content_source_type'] ?? $defaults['content_source_type'],
            'ai_prompt_template_id' => $data['ai_prompt_template_id'] ?? null,
            'custom_prompt' => $data['custom_prompt'] ?? null,
            'heading_length' => isset($data['heading_length']) ? (int) $data['heading_length'] : $defaults['heading_length'],
            'heading_emoji' => isset($data['heading_emoji']) ? (bool) $data['heading_emoji'] : $defaults['heading_emoji'],
            'caption_length' => isset($data['caption_length']) ? (int) $data['caption_length'] : $defaults['caption_length'],
            'hashtag_count' => isset($data['hashtag_count']) ? (int) $data['hashtag_count'] : $defaults['hashtag_count'],
            'default_caption_template_id' => $data['default_caption_template_id'] ?? $defaults['default_caption_template_id'],
            'default_loop_count' => isset($data['default_loop_count']) ? (int) $data['default_loop_count'] : $defaults['default_loop_count'],
            'default_enable_reverse' => isset($data['default_enable_reverse']) ? (bool) $data['default_enable_reverse'] : $defaults['default_enable_reverse'],
        ];

        if ($settings) {
            $settings->update($payload);
            return $settings->fresh();
        }

        return MediaUploadContentSettings::create(array_merge(['folder_id' => $folder->id], $payload));
    }
}
