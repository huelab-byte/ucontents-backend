<?php

declare(strict_types=1);

namespace Modules\FootageLibrary\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\StorageManagement\Models\StorageFile;
use Modules\UserManagement\Models\User;

class Footage extends Model
{
    use SoftDeletes;

    protected $table = 'footage';

    protected $fillable = [
        'storage_file_id',
        'folder_id',
        'title',
        'metadata',
        'embedding_id',
        'user_id',
        'status',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    /**
     * Get the storage file
     */
    public function storageFile(): BelongsTo
    {
        return $this->belongsTo(StorageFile::class);
    }

    /**
     * Get the folder
     */
    public function folder(): BelongsTo
    {
        return $this->belongsTo(FootageFolder::class);
    }

    /**
     * Get the user who uploaded the footage
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get metadata attribute with defaults
     */
    public function getMetadataAttribute($value): array
    {
        $metadata = is_array($value) ? $value : json_decode($value, true) ?? [];
        
        return array_merge([
            'description' => '',
            'tags' => [],
            'orientation' => 'horizontal',
            'duration' => 0.0,
            'resolution' => ['width' => 0, 'height' => 0],
            'fps' => 0,
            'format' => 'mp4',
            'extracted_frames' => [],
            'ai_metadata_source' => null,
        ], $metadata);
    }

    /**
     * Check if footage is ready for use
     */
    public function isReady(): bool
    {
        return $this->status === 'ready' && $this->embedding_id !== null;
    }
}
