<?php

declare(strict_types=1);

namespace Modules\ProxySetup\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\SocialConnection\Models\SocialConnectionChannel;

class ProxyChannelAssignment extends Model
{
    protected $table = 'proxy_channel_assignments';

    protected $fillable = [
        'proxy_id',
        'social_connection_channel_id',
    ];

    /**
     * Relationship with Proxy
     */
    public function proxy(): BelongsTo
    {
        return $this->belongsTo(Proxy::class);
    }

    /**
     * Relationship with SocialConnectionChannel
     */
    public function channel(): BelongsTo
    {
        return $this->belongsTo(SocialConnectionChannel::class, 'social_connection_channel_id');
    }
}
