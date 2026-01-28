<?php

declare(strict_types=1);

namespace Modules\ImageOverlay\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\StorageManagement\Models\StorageFile;
use Modules\UserManagement\Models\User;

class ImageOverlay extends Model
{
    use SoftDeletes;

    protected $table = 'image_overlays';

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
     * Get the default metadata structure
     */
    public function getMetadataAttribute($value): array
    {
        $default = [
            'description' => '',
            'tags' => [],
            'width' => null,
            'height' => null,
            'format' => null,
            'file_size' => null,
        ];

        $decoded = $value ? json_decode($value, true) : [];
        
        return array_merge($default, $decoded ?? []);
    }

    /**
     * Relationship with StorageFile
     */
    public function storageFile(): BelongsTo
    {
        return $this->belongsTo(StorageFile::class, 'storage_file_id');
    }

    /**
     * Relationship with ImageOverlayFolder
     */
    public function folder(): BelongsTo
    {
        return $this->belongsTo(ImageOverlayFolder::class, 'folder_id');
    }

    /**
     * Relationship with User
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
