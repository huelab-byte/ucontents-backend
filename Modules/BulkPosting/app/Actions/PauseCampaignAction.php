<?php

declare(strict_types=1);

namespace Modules\BulkPosting\Actions;

use Modules\BulkPosting\Models\BulkPostingCampaign;

class PauseCampaignAction
{
    public function execute(BulkPostingCampaign $campaign): BulkPostingCampaign
    {
        if ($campaign->status !== 'running') {
            return $campaign;
        }

        $campaign->update([
            'status' => 'paused',
            'paused_at' => now(),
        ]);

        return $campaign->fresh();
    }
}
