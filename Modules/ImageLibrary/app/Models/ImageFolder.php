<?php

declare(strict_types=1);

namespace Modules\ImageLibrary\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\UserManagement\Models\User;

class ImageFolder extends Model
{
    use SoftDeletes;

    protected $table = 'image_folders';

    protected $fillable = [
        'name',
        'parent_id',
        'user_id',
        'path',
    ];

    /**
     * Boot the model
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($folder) {
            $folder->path = static::buildPath($folder);
        });

        static::updating(function ($folder) {
            if ($folder->isDirty(['name', 'parent_id'])) {
                $folder->path = static::buildPath($folder);
            }
        });
    }

    /**
     * Build full path for folder
     */
    protected static function buildPath($folder): string
    {
        $parts = [$folder->name];
        $parent = $folder->parent_id ? static::find($folder->parent_id) : null;
        
        while ($parent) {
            array_unshift($parts, $parent->name);
            $parent = $parent->parent;
        }
        
        return implode('/', $parts);
    }

    /**
     * Parent folder relationship
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(ImageFolder::class, 'parent_id');
    }

    /**
     * Child folders relationship
     */
    public function children(): HasMany
    {
        return $this->hasMany(ImageFolder::class, 'parent_id');
    }

    /**
     * Images in folder relationship
     */
    public function images(): HasMany
    {
        return $this->hasMany(Image::class, 'folder_id');
    }

    /**
     * User relationship
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
