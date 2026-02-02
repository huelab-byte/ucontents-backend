<?php

declare(strict_types=1);

namespace Modules\BulkPosting\DTOs;

readonly class CreateCampaignDTO
{
    public function __construct(
        public string $brandName,
        public string $projectName,
        public ?int $brandLogoStorageFileId = null,
        public string $contentSourceType = 'media_upload',
        public array $contentSourceConfig = [],
        public string $scheduleCondition = 'daily',
        public int $scheduleInterval = 1,
        public bool $repostEnabled = false,
        public ?string $repostCondition = null,
        public int $repostInterval = 0,
        public int $repostMaxCount = 1,
        public array $channelIds = [],
        public array $groupIds = [],
    ) {}

    public static function fromArray(array $data): self
    {
        $connections = $data['connections'] ?? ['channels' => [], 'groups' => []];

        return new self(
            brandName: $data['brand_name'],
            projectName: $data['project_name'],
            brandLogoStorageFileId: $data['brand_logo_storage_file_id'] ?? null,
            contentSourceType: $data['content_source_type'] ?? 'media_upload',
            contentSourceConfig: $data['content_source_config'] ?? [],
            scheduleCondition: $data['schedule_condition'] ?? 'daily',
            scheduleInterval: (int) ($data['schedule_interval'] ?? 1),
            repostEnabled: (bool) ($data['repost_enabled'] ?? false),
            repostCondition: $data['repost_condition'] ?? null,
            repostInterval: (int) ($data['repost_interval'] ?? 0),
            repostMaxCount: (int) ($data['repost_max_count'] ?? 1),
            channelIds: $connections['channels'] ?? [],
            groupIds: $connections['groups'] ?? [],
        );
    }
}
