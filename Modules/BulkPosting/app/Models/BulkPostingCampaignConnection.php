<?php

declare(strict_types=1);

namespace Modules\BulkPosting\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BulkPostingCampaignConnection extends Model
{
    protected $table = 'bulk_posting_campaign_connections';

    protected $fillable = [
        'bulk_posting_campaign_id',
        'connection_type', // channel | group
        'connection_id',
    ];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(BulkPostingCampaign::class, 'bulk_posting_campaign_id');
    }
}
