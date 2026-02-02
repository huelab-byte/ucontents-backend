<?php

declare(strict_types=1);

namespace Modules\BulkPosting\Actions;

use Modules\BulkPosting\DTOs\UpdateCampaignDTO;
use Modules\BulkPosting\Models\BulkPostingCampaign;
use Modules\BulkPosting\Models\BulkPostingCampaignConnection as CampaignConnection;

class UpdateCampaignAction
{
    public function execute(BulkPostingCampaign $campaign, UpdateCampaignDTO $dto): BulkPostingCampaign
    {
        $updates = array_filter([
            'brand_name' => $dto->brandName,
            'project_name' => $dto->projectName,
            'brand_logo_storage_file_id' => $dto->brandLogoStorageFileId,
            'content_source_type' => $dto->contentSourceType,
            'content_source_config' => $dto->contentSourceConfig,
            'schedule_condition' => $dto->scheduleCondition,
            'schedule_interval' => $dto->scheduleInterval,
            'repost_enabled' => $dto->repostEnabled,
            'repost_condition' => $dto->repostCondition,
            'repost_interval' => $dto->repostInterval,
            'repost_max_count' => $dto->repostMaxCount,
        ], fn ($v) => $v !== null);

        if (! empty($updates)) {
            $campaign->update($updates);
        }

        if ($dto->channelIds !== null || $dto->groupIds !== null) {
            $campaign->connections()->delete();
            $channelIds = $dto->channelIds ?? [];
            $groupIds = $dto->groupIds ?? [];

            foreach ($channelIds as $channelId) {
                CampaignConnection::create([
                    'bulk_posting_campaign_id' => $campaign->id,
                    'connection_type' => 'channel',
                    'connection_id' => $channelId,
                ]);
            }

            foreach ($groupIds as $groupId) {
                CampaignConnection::create([
                    'bulk_posting_campaign_id' => $campaign->id,
                    'connection_type' => 'group',
                    'connection_id' => $groupId,
                ]);
            }
        }

        return $campaign->fresh();
    }
}
