<?php

declare(strict_types=1);

namespace Modules\BulkPosting\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BulkPostingContentItem extends Model
{
    protected $table = 'bulk_posting_content_items';

    protected $fillable = [
        'bulk_posting_campaign_id',
        'source_type',
        'source_ref',
        'payload',
        'status',
        'scheduled_at',
        'published_at',
        'republish_count',
        'error_message',
        'external_post_ids',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'external_post_ids' => 'array',
            'scheduled_at' => 'datetime',
            'published_at' => 'datetime',
            'republish_count' => 'integer',
        ];
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(BulkPostingCampaign::class, 'bulk_posting_campaign_id');
    }
}
