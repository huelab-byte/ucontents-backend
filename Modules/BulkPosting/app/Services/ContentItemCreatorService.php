<?php

declare(strict_types=1);

namespace Modules\BulkPosting\Services;

use Modules\BulkPosting\Models\BulkPostingCampaign;
use Modules\BulkPosting\Models\BulkPostingContentItem;
use Modules\MediaUpload\Models\MediaUpload;
use Modules\StorageManagement\Factories\StorageDriverFactory;
use Modules\StorageManagement\Models\StorageFile;

class ContentItemCreatorService
{
    public function __construct(
        private CsvParserService $csvParser
    ) {}

    /**
     * Create content items for a campaign based on content source
     */
    public function createContentItems(BulkPostingCampaign $campaign): int
    {
        $config = $campaign->content_source_config ?? [];

        if ($campaign->content_source_type === 'media_upload') {
            return $this->createFromMediaUpload($campaign, $config['folder_ids'] ?? []);
        }

        if ($campaign->content_source_type === 'csv_file') {
            $storageFileId = $config['csv_storage_file_id'] ?? null;
            if ($storageFileId) {
                return $this->createFromCsv($campaign, (int) $storageFileId, $campaign->user_id);
            }
        }

        return 0;
    }

    protected function createFromMediaUpload(BulkPostingCampaign $campaign, array $folderIds): int
    {
        if (empty($folderIds)) {
            return 0;
        }

        $mediaUploads = MediaUpload::with('storageFile')
            ->whereIn('folder_id', $folderIds)
            ->where('user_id', $campaign->user_id)
            ->where('status', 'ready')
            ->orderBy('id')
            ->get();

        $count = 0;
        foreach ($mediaUploads as $media) {
            $mediaUrl = $media->storageFile?->url;
            if (! $mediaUrl && $media->storageFile) {
                try {
                    $driver = StorageDriverFactory::make($media->storageFile->driver);
                    $mediaUrl = $driver->url($media->storageFile->path);
                } catch (\Throwable) {
                    $mediaUrl = null;
                }
            }
            $payload = [
                'caption' => $media->social_caption ?? $media->title ?? '',
                'media_urls' => $mediaUrl ? [$mediaUrl] : [],
                'hashtags' => is_array($media->hashtags) ? $media->hashtags : [],
                'youtube_heading' => $media->youtube_heading,
            ];

            BulkPostingContentItem::create([
                'bulk_posting_campaign_id' => $campaign->id,
                'source_type' => 'media_upload',
                'source_ref' => (string) $media->id,
                'payload' => $payload,
                'status' => 'pending',
            ]);
            $count++;
        }

        return $count;
    }

    protected function createFromCsv(BulkPostingCampaign $campaign, int $storageFileId, int $userId): int
    {
        $storageFile = StorageFile::find($storageFileId);
        if (! $storageFile || $storageFile->user_id !== $userId) {
            return 0;
        }

        $rows = $this->csvParser->parse($storageFile);
        $count = 0;

        foreach ($rows as $index => $row) {
            $payload = [
                'caption' => $row['caption'] ?? $row['text'] ?? '',
                'media_urls' => isset($row['media_url']) ? [$row['media_url']] : ($row['media_urls'] ?? []),
                'hashtags' => is_array($row['hashtags'] ?? null) ? $row['hashtags'] : (isset($row['hashtags']) ? explode(' ', (string) $row['hashtags']) : []),
                'youtube_heading' => $row['youtube_heading'] ?? null,
            ];

            BulkPostingContentItem::create([
                'bulk_posting_campaign_id' => $campaign->id,
                'source_type' => 'csv',
                'source_ref' => (string) $index,
                'payload' => $payload,
                'status' => 'pending',
            ]);
            $count++;
        }

        return $count;
    }
}
