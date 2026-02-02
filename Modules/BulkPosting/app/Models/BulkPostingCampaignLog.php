<?php

declare(strict_types=1);

namespace Modules\BulkPosting\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BulkPostingCampaignLog extends Model
{
    protected $table = 'bulk_posting_campaign_logs';

    protected $fillable = [
        'bulk_posting_campaign_id',
        'bulk_posting_content_item_id',
        'event_type',
        'payload',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
        ];
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(BulkPostingCampaign::class, 'bulk_posting_campaign_id');
    }

    public function contentItem(): BelongsTo
    {
        return $this->belongsTo(BulkPostingContentItem::class, 'bulk_posting_content_item_id');
    }
}
