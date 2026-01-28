<?php

declare(strict_types=1);

namespace Modules\BgmLibrary\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\UserManagement\Models\User;

class BgmUploadQueue extends Model
{
    protected $table = 'bgm_upload_queue';

    protected $fillable = [
        'user_id',
        'file_name',
        'file_path',
        'file_size',
        'mime_type',
        'folder_id',
        'metadata_source',
        'status',
        'progress',
        'error_message',
        'bgm_id',
        'attempts',
        'processed_at',
    ];

    protected $casts = [
        'processed_at' => 'datetime',
    ];

    /**
     * Get the user who queued the upload
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the folder
     */
    public function folder(): BelongsTo
    {
        return $this->belongsTo(BgmFolder::class);
    }

    /**
     * Get the processed BGM
     */
    public function bgm(): BelongsTo
    {
        return $this->belongsTo(Bgm::class);
    }

    /**
     * Mark as processing
     */
    public function markAsProcessing(): void
    {
        $this->update([
            'status' => 'processing',
            'progress' => 10,
        ]);
    }

    /**
     * Update progress
     */
    public function updateProgress(int $progress): void
    {
        $this->update(['progress' => min(100, max(0, $progress))]);
    }

    /**
     * Mark as completed
     */
    public function markAsCompleted(int $bgmId): void
    {
        $this->update([
            'status' => 'completed',
            'progress' => 100,
            'bgm_id' => $bgmId,
            'processed_at' => now(),
        ]);
    }

    /**
     * Mark as failed
     */
    public function markAsFailed(string $errorMessage): void
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $errorMessage,
            'attempts' => $this->attempts + 1,
        ]);
    }
}
