<?php

declare(strict_types=1);

namespace Modules\MediaUpload\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\StorageManagement\Models\StorageFile;
use Modules\UserManagement\Models\User;

class MediaUpload extends Model
{
    protected $fillable = [
        'user_id',
        'folder_id',
        'storage_file_id',
        'title',
        'status',
        'caption_template_id',
        'loop_count',
        'enable_reverse',
        'youtube_heading',
        'social_caption',
        'hashtags',
        'video_metadata',
        'processed_at',
    ];

    protected $casts = [
        'hashtags' => 'array',
        'video_metadata' => 'array',
        'enable_reverse' => 'boolean',
        'loop_count' => 'integer',
        'processed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function folder(): BelongsTo
    {
        return $this->belongsTo(MediaUploadFolder::class);
    }

    public function storageFile(): BelongsTo
    {
        return $this->belongsTo(StorageFile::class);
    }

    public function captionTemplate(): BelongsTo
    {
        return $this->belongsTo(CaptionTemplate::class);
    }

    public function isReady(): bool
    {
        return $this->status === 'ready';
    }
}
