<?php

declare(strict_types=1);

namespace Modules\MediaUpload\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\UserManagement\Models\User;

class CaptionTemplate extends Model
{
    protected $table = 'media_upload_caption_templates';

    protected $fillable = [
        'user_id',
        'name',
        'font',
        'font_size',
        'font_weight',
        'font_color',
        'outline_color',
        'outline_size',
        'position',
        'position_offset',
        'words_per_caption',
        'word_highlighting',
        'highlight_color',
        'highlight_style',
        'background_opacity',
        'enable_alternating_loop',
        'loop_count',
    ];

    protected $casts = [
        'word_highlighting' => 'boolean',
        'enable_alternating_loop' => 'boolean',
        'font_size' => 'integer',
        'outline_size' => 'integer',
        'position_offset' => 'integer',
        'words_per_caption' => 'integer',
        'background_opacity' => 'integer',
        'loop_count' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
