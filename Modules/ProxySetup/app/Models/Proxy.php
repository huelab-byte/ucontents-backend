<?php

declare(strict_types=1);

namespace Modules\ProxySetup\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\SocialConnection\Models\SocialConnectionChannel;
use Modules\UserManagement\Models\User;

class Proxy extends Model
{
    use SoftDeletes;

    protected $table = 'proxies';

    protected $fillable = [
        'user_id',
        'name',
        'type',
        'host',
        'port',
        'username',
        'password',
        'is_enabled',
        'last_checked_at',
        'last_check_status',
        'last_check_message',
    ];

    protected function casts(): array
    {
        return [
            'port' => 'integer',
            'is_enabled' => 'boolean',
            'last_checked_at' => 'datetime',
            'username' => 'encrypted',
            'password' => 'encrypted',
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
     * Relationship with ProxyChannelAssignment
     */
    public function channelAssignments(): HasMany
    {
        return $this->hasMany(ProxyChannelAssignment::class);
    }

    /**
     * Relationship with SocialConnectionChannel (through pivot)
     */
    public function channels(): BelongsToMany
    {
        return $this->belongsToMany(
            SocialConnectionChannel::class,
            'proxy_channel_assignments',
            'proxy_id',
            'social_connection_channel_id'
        )->withTimestamps();
    }

    /**
     * Get the proxy URL for cURL usage
     */
    public function getProxyUrl(): string
    {
        $auth = '';
        if ($this->username && $this->password) {
            $auth = urlencode($this->username) . ':' . urlencode($this->password) . '@';
        }

        $scheme = match ($this->type) {
            'socks4' => 'socks4',
            'socks5' => 'socks5',
            default => 'http',
        };

        return "{$scheme}://{$auth}{$this->host}:{$this->port}";
    }
}
