<?php

declare(strict_types=1);

namespace Modules\BulkPosting\Actions;

use Modules\BulkPosting\Models\BulkPostingCampaign;

class ResumeCampaignAction
{
    public function execute(BulkPostingCampaign $campaign): BulkPostingCampaign
    {
        if ($campaign->status !== 'paused') {
            return $campaign;
        }

        $campaign->update([
            'status' => 'running',
            'paused_at' => null,
        ]);

        return $campaign->fresh();
    }
}
