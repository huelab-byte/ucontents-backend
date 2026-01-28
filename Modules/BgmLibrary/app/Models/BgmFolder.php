<?php

declare(strict_types=1);

namespace Modules\BgmLibrary\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\UserManagement\Models\User;

class BgmFolder extends Model
{
    use SoftDeletes;

    protected $table = 'bgm_folders';

    protected $fillable = [
        'name',
        'parent_id',
        'user_id',
        'path',
    ];

    /**
     * Get the user who owns the folder
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the parent folder
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(BgmFolder::class, 'parent_id');
    }

    /**
     * Get child folders
     */
    public function children(): HasMany
    {
        return $this->hasMany(BgmFolder::class, 'parent_id');
    }

    /**
     * Get all BGM in this folder
     */
    public function bgm(): HasMany
    {
        return $this->hasMany(Bgm::class, 'folder_id');
    }

    /**
     * Calculate and set the full path for this folder
     */
    public function calculatePath(): string
    {
        $path = [$this->name];
        $parent = $this->parent;

        while ($parent) {
            array_unshift($path, $parent->name);
            $parent = $parent->parent;
        }

        return implode('/', $path);
    }

    /**
     * Boot the model
     */
    protected static function boot(): void
    {
        parent::boot();

        static::saving(function ($folder) {
            if ($folder->isDirty('name') || $folder->isDirty('parent_id')) {
                $folder->path = $folder->calculatePath();
            }
        });

        static::saved(function ($folder) {
            // Update children paths when parent changes
            foreach ($folder->children as $child) {
                $child->path = $child->calculatePath();
                $child->saveQuietly();
            }
        });
    }
}
