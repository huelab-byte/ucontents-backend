<?php

declare(strict_types=1);

namespace Modules\ProxySetup\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\UserManagement\Models\User;

class ProxySetting extends Model
{
    protected $table = 'proxy_settings';

    protected $fillable = [
        'user_id',
        'use_random_proxy',
        'apply_to_all_channels',
        'on_proxy_failure',
    ];

    protected function casts(): array
    {
        return [
            'use_random_proxy' => 'boolean',
            'apply_to_all_channels' => 'boolean',
        ];
    }

    /**
     * Relationship with User
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get or create settings for a user
     */
    public static function getOrCreateForUser(int $userId): self
    {
        return self::firstOrCreate(
            ['user_id' => $userId],
            [
                'use_random_proxy' => false,
                'apply_to_all_channels' => true,
                'on_proxy_failure' => 'continue_without_proxy',
            ]
        );
    }
}
