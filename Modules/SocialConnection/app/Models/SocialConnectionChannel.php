<?php

declare(strict_types=1);

namespace Modules\SocialConnection\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Modules\UserManagement\Models\User;

class SocialConnectionChannel extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'social_connection_account_id',
        'group_id',
        'provider',
        'type',
        'provider_channel_id',
        'name',
        'username',
        'avatar_url',
        'is_active',
        'metadata',
        'token_context',
        'connected_via_package_id',
        'labels',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'metadata' => 'array',
            // This may contain access tokens (e.g. Meta page token); keep encrypted.
            'token_context' => 'encrypted:array',
            'labels' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(SocialConnectionAccount::class, 'social_connection_account_id');
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(SocialConnectionGroup::class, 'group_id');
    }

    /**
     * Relationship with Proxies (through pivot table)
     */
    public function proxies(): BelongsToMany
    {
        return $this->belongsToMany(
            \Modules\ProxySetup\Models\Proxy::class,
            'proxy_channel_assignments',
            'social_connection_channel_id',
            'proxy_id'
        )->withTimestamps();
    }
}

