<?php

declare(strict_types=1);

namespace Modules\BulkPosting\Actions;

use Modules\BulkPosting\Models\BulkPostingCampaign;

class StartCampaignAction
{
    public function execute(BulkPostingCampaign $campaign): BulkPostingCampaign
    {
        if ($campaign->status === 'running') {
            return $campaign;
        }

        $campaign->update([
            'status' => 'running',
            'started_at' => $campaign->started_at ?? now(),
            'paused_at' => null,
        ]);

        return $campaign->fresh();
    }
}
