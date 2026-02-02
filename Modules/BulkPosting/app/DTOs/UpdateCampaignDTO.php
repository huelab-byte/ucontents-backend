<?php

declare(strict_types=1);

namespace Modules\BulkPosting\DTOs;

readonly class UpdateCampaignDTO
{
    public function __construct(
        public ?string $brandName = null,
        public ?string $projectName = null,
        public ?int $brandLogoStorageFileId = null,
        public ?string $contentSourceType = null,
        public ?array $contentSourceConfig = null,
        public ?string $scheduleCondition = null,
        public ?int $scheduleInterval = null,
        public ?bool $repostEnabled = null,
        public ?string $repostCondition = null,
        public ?int $repostInterval = null,
        public ?int $repostMaxCount = null,
        public ?array $channelIds = null,
        public ?array $groupIds = null,
    ) {}

    public static function fromArray(array $data): self
    {
        $connections = $data['connections'] ?? null;
        $channelIds = null;
        $groupIds = null;
        if ($connections !== null) {
            $channelIds = $connections['channels'] ?? [];
            $groupIds = $connections['groups'] ?? [];
        }

        return new self(
            brandName: $data['brand_name'] ?? null,
            projectName: $data['project_name'] ?? null,
            brandLogoStorageFileId: $data['brand_logo_storage_file_id'] ?? null,
            contentSourceType: $data['content_source_type'] ?? null,
            contentSourceConfig: $data['content_source_config'] ?? null,
            scheduleCondition: $data['schedule_condition'] ?? null,
            scheduleInterval: isset($data['schedule_interval']) ? (int) $data['schedule_interval'] : null,
            repostEnabled: isset($data['repost_enabled']) ? (bool) $data['repost_enabled'] : null,
            repostCondition: $data['repost_condition'] ?? null,
            repostInterval: isset($data['repost_interval']) ? (int) $data['repost_interval'] : null,
            repostMaxCount: isset($data['repost_max_count']) ? (int) $data['repost_max_count'] : null,
            channelIds: $channelIds,
            groupIds: $groupIds,
        );
    }
}
