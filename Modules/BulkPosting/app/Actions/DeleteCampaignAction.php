<?php

declare(strict_types=1);

namespace Modules\BulkPosting\Actions;

use Modules\BulkPosting\Models\BulkPostingCampaign;

class DeleteCampaignAction
{
    public function execute(BulkPostingCampaign $campaign): void
    {
        $campaign->delete();
    }
}
