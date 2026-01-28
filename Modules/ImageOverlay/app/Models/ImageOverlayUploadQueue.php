<?php

declare(strict_types=1);

namespace Modules\ImageOverlay\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\UserManagement\Models\User;

class ImageOverlayUploadQueue extends Model
{
    protected $table = 'image_overlay_upload_queue';

    protected $fillable = [
        'user_id',
        'file_name',
        'file_path',
        'file_size',
        'mime_type',
        'folder_id',
        'status',
        'progress',
        'error_message',
        'image_overlay_id',
        'attempts',
        'processed_at',
    ];

    protected $casts = [
        'progress' => 'integer',
        'processed_at' => 'datetime',
    ];

    /**
     * User relationship
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * ImageOverlay relationship
     */
    public function imageOverlay(): BelongsTo
    {
        return $this->belongsTo(ImageOverlay::class);
    }

    /**
     * Folder relationship
     */
    public function folder(): BelongsTo
    {
        return $this->belongsTo(ImageOverlayFolder::class, 'folder_id');
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
        $this->update(['progress' => $progress]);
    }

    /**
     * Mark as completed
     */
    public function markAsCompleted(int $imageOverlayId): void
    {
        $this->update([
            'status' => 'completed',
            'progress' => 100,
            'image_overlay_id' => $imageOverlayId,
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
