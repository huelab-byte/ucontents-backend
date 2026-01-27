<?php

declare(strict_types=1);

namespace Modules\ImageLibrary\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\UserManagement\Models\User;

class ImageUploadQueue extends Model
{
    protected $table = 'image_upload_queue';

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
        'image_id',
    ];

    protected $casts = [
        'progress' => 'integer',
    ];

    /**
     * User relationship
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Image relationship
     */
    public function image(): BelongsTo
    {
        return $this->belongsTo(Image::class);
    }

    /**
     * Folder relationship
     */
    public function folder(): BelongsTo
    {
        return $this->belongsTo(ImageFolder::class, 'folder_id');
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
    public function markAsCompleted(int $imageId): void
    {
        $this->update([
            'status' => 'completed',
            'progress' => 100,
            'image_id' => $imageId,
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
        ]);
    }
}
