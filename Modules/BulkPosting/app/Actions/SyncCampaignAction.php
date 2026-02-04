<?php

declare(strict_types=1);

namespace Modules\BulkPosting\Actions;

use Illuminate\Support\Facades\Log;
use Modules\BulkPosting\Models\BulkPostingCampaign;
use Modules\BulkPosting\Models\BulkPostingCampaignLog;
use Modules\BulkPosting\Models\BulkPostingContentItem;
use Modules\MediaUpload\Models\MediaUpload;
use Modules\StorageManagement\Factories\StorageDriverFactory;

/**
 * Action to sync campaign content items with folder changes
 * Only works for campaigns with content_source_type = 'media_upload'
 */
class SyncCampaignAction
{
    /**
     * Sync campaign content items with current folder contents
     *
     * @param BulkPostingCampaign $campaign
     * @return array{added: int, skipped: int, total: int}
     */
    public function execute(BulkPostingCampaign $campaign): array
    {
        if ($campaign->content_source_type !== 'media_upload') {
            return [
                'added' => 0,
                'skipped' => 0,
                'total' => 0,
                'error' => 'Sync only works for media_upload content source type',
            ];
        }

        $config = $campaign->content_source_config ?? [];
        $folderIds = $config['folder_ids'] ?? [];

        if (empty($folderIds)) {
            return [
                'added' => 0,
                'skipped' => 0,
                'total' => 0,
                'error' => 'No folders configured for this campaign',
            ];
        }

        // Get existing content item source refs (media upload IDs)
        $existingSourceRefs = BulkPostingContentItem::where('bulk_posting_campaign_id', $campaign->id)
            ->where('source_type', 'media_upload')
            ->pluck('source_ref')
            ->map(fn ($ref) => (int) $ref)
            ->toArray();

        // Get all media uploads from the configured folders
        $mediaUploads = MediaUpload::with('storageFile')
            ->whereIn('folder_id', $folderIds)
            ->where('user_id', $campaign->user_id)
            ->where('status', 'ready')
            ->orderBy('id')
            ->get();

        $added = 0;
        $skipped = 0;

        foreach ($mediaUploads as $media) {
            // Skip if already exists
            if (in_array($media->id, $existingSourceRefs, true)) {
                $skipped++;
                continue;
            }

            // Create new content item
            $mediaUrl = $media->storageFile?->url;
            if (!$mediaUrl && $media->storageFile) {
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

            $added++;
        }

        // Log the sync event and update campaign status if new items added
        if ($added > 0) {
            // If campaign was completed or failed, change to paused so user can restart it
            if (in_array($campaign->status, ['completed', 'failed'], true)) {
                $campaign->update(['status' => 'paused']);
            }

            BulkPostingCampaignLog::create([
                'bulk_posting_campaign_id' => $campaign->id,
                'bulk_posting_content_item_id' => null,
                'event_type' => 'campaign_synced',
                'payload' => [
                    'added' => $added,
                    'skipped' => $skipped,
                    'total' => $mediaUploads->count(),
                    'status_changed' => in_array($campaign->status, ['completed', 'failed'], true),
                ],
            ]);

            Log::info('BulkPosting: Campaign synced', [
                'campaign_id' => $campaign->id,
                'added' => $added,
                'skipped' => $skipped,
            ]);
        }

        return [
            'added' => $added,
            'skipped' => $skipped,
            'total' => $mediaUploads->count(),
            'status' => $campaign->fresh()->status,
        ];
    }
}
