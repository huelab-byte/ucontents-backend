<?php

declare(strict_types=1);

namespace Modules\StorageManagement\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\UserManagement\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StorageUploadQueue extends Model
{
    protected $table = 'storage_upload_queue';

    protected $fillable = [
        'user_id',
        'file_name',
        'file_path',
        'file_size',
        'mime_type',
        'status',
        'progress',
        'error_message',
        'metadata',
        'storage_file_id',
        'attempts',
        'processed_at',
    ];

    protected $casts = [
        'file_size' => 'integer',
        'progress' => 'integer',
        'metadata' => 'array',
        'attempts' => 'integer',
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
     * Get the storage file after upload
     */
    public function storageFile(): BelongsTo
    {
        return $this->belongsTo(StorageFile::class);
    }
}
