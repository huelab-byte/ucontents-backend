<?php

declare(strict_types=1);

namespace Modules\StorageManagement\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\UserManagement\Models\User;
use Modules\StorageManagement\Factories\StorageDriverFactory;

class StorageFile extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'driver',
        'path',
        'original_name',
        'mime_type',
        'size',
        'disk',
        'url',
        'user_id',
        'reference_type',
        'reference_id',
        'is_used',
        'last_accessed_at',
    ];

    protected $casts = [
        'size' => 'integer',
        'user_id' => 'integer',
        'is_used' => 'boolean',
        'last_accessed_at' => 'datetime',
    ];

    /**
     * Get the user who uploaded the file
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the parent reference model
     */
    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Mark file as accessed
     */
    public function markAsAccessed(): void
    {
        $this->update(['last_accessed_at' => now()]);
    }

    /**
     * Get local filesystem path for processing
     * For local storage, returns the actual path
     * For remote storage (S3), downloads to temp and returns temp path
     *
     * @return string Local filesystem path
     */
    public function getLocalPath(): string
    {
        $driver = StorageDriverFactory::make($this->driver);
        return $driver->getLocalPath($this->path);
    }

    /**
     * Cleanup temporary file if it was created for processing
     *
     * @param string $localPath Path returned by getLocalPath
     * @return void
     */
    public function cleanupLocalPath(string $localPath): void
    {
        $driver = StorageDriverFactory::make($this->driver);
        $driver->cleanupLocalPath($localPath, $this->path);
    }
}
