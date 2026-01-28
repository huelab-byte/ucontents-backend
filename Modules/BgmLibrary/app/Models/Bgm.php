<?php

declare(strict_types=1);

namespace Modules\BgmLibrary\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\StorageManagement\Models\StorageFile;
use Modules\UserManagement\Models\User;

class Bgm extends Model
{
    use SoftDeletes;

    protected $table = 'bgm';

    protected $fillable = [
        'storage_file_id',
        'folder_id',
        'title',
        'metadata',
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
        return $this->belongsTo(BgmFolder::class);
    }

    /**
     * Get the user who uploaded the BGM
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
            'duration' => 0.0,
            'bitrate' => 0,
            'sample_rate' => 0,
            'channels' => 0,
            'format' => 'mp3',
            'ai_metadata_source' => null,
        ], $metadata);
    }

    /**
     * Check if BGM is ready for use
     */
    public function isReady(): bool
    {
        return $this->status === 'ready';
    }
}
