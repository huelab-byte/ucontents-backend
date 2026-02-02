<?php

declare(strict_types=1);

namespace Modules\MediaUpload\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MediaUploadContentSettings extends Model
{
    protected $fillable = [
        'folder_id',
        'content_source_type',
        'ai_prompt_template_id',
        'custom_prompt',
        'heading_length',
        'heading_emoji',
        'caption_length',
        'hashtag_count',
        'default_caption_template_id',
        'default_loop_count',
        'default_enable_reverse',
    ];

    protected $casts = [
        'heading_length' => 'integer',
        'heading_emoji' => 'boolean',
        'caption_length' => 'integer',
        'hashtag_count' => 'integer',
        'default_loop_count' => 'integer',
        'default_enable_reverse' => 'boolean',
    ];

    public function folder(): BelongsTo
    {
        return $this->belongsTo(MediaUploadFolder::class);
    }
}
