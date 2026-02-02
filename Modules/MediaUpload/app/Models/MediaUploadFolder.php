<?php

declare(strict_types=1);

namespace Modules\MediaUpload\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Modules\UserManagement\Models\User;

class MediaUploadFolder extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'storage_path',
        'parent_id',
    ];

    /**
     * Get the full storage path for this folder including parent hierarchy
     * e.g., media-upload/{parentStoragePath}/{storagePath}
     * 
     * Falls back to folder-{id} if storage_path is not set (backward compatibility)
     */
    public function getFullStoragePath(): string
    {
        $basePath = 'media-upload';
        
        // Use storage_path if available, otherwise fallback to folder-{id} for backward compatibility
        $folderPath = $this->storage_path ?? ('folder-' . $this->id);
        
        // Build parent path if nested
        if ($this->parent) {
            $parentPath = $this->parent->storage_path ?? ('folder-' . $this->parent->id);
            return $basePath . '/' . $parentPath . '/' . $folderPath;
        }

        return $basePath . '/' . $folderPath;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(MediaUploadFolder::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(MediaUploadFolder::class, 'parent_id');
    }

    public function mediaUploads(): HasMany
    {
        return $this->hasMany(MediaUpload::class, 'folder_id');
    }

    public function contentSettings(): HasOne
    {
        return $this->hasOne(MediaUploadContentSettings::class, 'folder_id');
    }

    public function queueItems(): HasMany
    {
        return $this->hasMany(MediaUploadQueue::class, 'folder_id');
    }
}
