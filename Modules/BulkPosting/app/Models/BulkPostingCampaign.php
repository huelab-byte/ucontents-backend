<?php

declare(strict_types=1);

namespace Modules\BulkPosting\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\StorageManagement\Models\StorageFile;
use Modules\UserManagement\Models\User;

class BulkPostingCampaign extends Model
{
    protected $table = 'bulk_posting_campaigns';

    protected $fillable = [
        'user_id',
        'brand_name',
        'project_name',
        'brand_logo_storage_file_id',
        'content_source_type',
        'content_source_config',
        'schedule_condition',
        'schedule_interval',
        'repost_enabled',
        'repost_condition',
        'repost_interval',
        'repost_max_count',
        'status',
        'started_at',
        'paused_at',
        'last_post_at',
        'last_repost_at',
    ];

    protected function casts(): array
    {
        return [
            'content_source_config' => 'array',
            'repost_enabled' => 'boolean',
            'schedule_interval' => 'integer',
            'repost_interval' => 'integer',
            'repost_max_count' => 'integer',
            'started_at' => 'datetime',
            'paused_at' => 'datetime',
            'last_post_at' => 'datetime',
            'last_repost_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function brandLogo(): BelongsTo
    {
        return $this->belongsTo(StorageFile::class, 'brand_logo_storage_file_id');
    }

    public function connections(): HasMany
    {
        return $this->hasMany(BulkPostingCampaignConnection::class, 'bulk_posting_campaign_id');
    }

    public function contentItems(): HasMany
    {
        return $this->hasMany(BulkPostingContentItem::class, 'bulk_posting_campaign_id');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(BulkPostingCampaignLog::class, 'bulk_posting_campaign_id');
    }
}
