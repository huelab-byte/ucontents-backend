<?php

declare(strict_types=1);

namespace Modules\StorageManagement\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StorageSetting extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'driver',
        'is_active',
        'key',
        'secret',
        'region',
        'bucket',
        'endpoint',
        'url',
        'use_path_style_endpoint',
        'root_path',
        'metadata',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'use_path_style_endpoint' => 'boolean',
        'metadata' => 'array',
    ];

    /**
     * Get the active storage setting
     */
    public static function getActive(): ?self
    {
        return static::where('is_active', true)->first();
    }

    /**
     * Activate this storage setting (deactivates others)
     */
    public function activate(): void
    {
        static::where('id', '!=', $this->id)->update(['is_active' => false]);
        $this->update(['is_active' => true]);
    }
}
