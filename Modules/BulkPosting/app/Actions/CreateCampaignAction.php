<?php

declare(strict_types=1);

namespace Modules\BulkPosting\Actions;

use Modules\BulkPosting\DTOs\CreateCampaignDTO;
use Modules\BulkPosting\Models\BulkPostingCampaign;
use Modules\BulkPosting\Models\BulkPostingCampaignConnection as CampaignConnection;
use Modules\BulkPosting\Services\ContentItemCreatorService;
use Modules\UserManagement\Models\User;

class CreateCampaignAction
{
    public function __construct(
        private ContentItemCreatorService $contentItemCreator
    ) {}

    public function execute(User $user, CreateCampaignDTO $dto): BulkPostingCampaign
    {
        $campaign = BulkPostingCampaign::create([
            'user_id' => $user->id,
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
            'status' => 'draft',
        ]);

        $this->syncConnections($campaign, $dto->channelIds, $dto->groupIds);

        $this->contentItemCreator->createContentItems($campaign);

        return $campaign;
    }

    protected function syncConnections(BulkPostingCampaign $campaign, array $channelIds, array $groupIds): void
    {
        $campaign->connections()->delete();

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
}
