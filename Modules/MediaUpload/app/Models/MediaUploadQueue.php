<?php

declare(strict_types=1);

namespace Modules\MediaUpload\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\UserManagement\Models\User;

class MediaUploadQueue extends Model
{
    protected $table = 'media_upload_queue';

    protected $fillable = [
        'user_id',
        'folder_id',
        'file_name',
        'file_path',
        'file_size',
        'mime_type',
        'caption_config',
        'status',
        'progress',
        'error_message',
        'media_upload_id',
        'attempts',
        'processed_at',
    ];

    protected $casts = [
        'processed_at' => 'datetime',
        'file_size' => 'integer',
        'caption_config' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function folder(): BelongsTo
    {
        return $this->belongsTo(MediaUploadFolder::class);
    }

    public function mediaUpload(): BelongsTo
    {
        return $this->belongsTo(MediaUpload::class);
    }

    public function markAsProcessing(): void
    {
        $this->update([
            'status' => 'processing',
            'progress' => 10,
        ]);
    }

    public function updateProgress(int $progress): void
    {
        $this->update(['progress' => min(100, max(0, $progress))]);
    }

    public function markAsCompleted(int $mediaUploadId): void
    {
        $this->update([
            'status' => 'completed',
            'progress' => 100,
            'media_upload_id' => $mediaUploadId,
            'processed_at' => now(),
        ]);
    }

    public function markAsFailed(string $errorMessage): void
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $errorMessage,
            'attempts' => $this->attempts + 1,
        ]);
    }
}
