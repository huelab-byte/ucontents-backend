<?php

declare(strict_types=1);

namespace Modules\BulkPosting\Services;

use Modules\BulkPosting\Models\BulkPostingContentItem;
use Modules\MediaUpload\Models\MediaUpload;
use Modules\StorageManagement\Factories\StorageDriverFactory;
use Modules\StorageManagement\Models\StorageFile;

class ContentResolverService
{
    /**
     * Resolve content payload for a content item (caption, media URLs, hashtags)
     *
     * @return array{caption: string, media_urls: string[], hashtags: string[]}
     */
    public function resolvePayload(BulkPostingContentItem $contentItem): array
    {
        if ($contentItem->payload && is_array($contentItem->payload)) {
            $payload = $contentItem->payload;

            // Enrich with media_items if missing and we have a media_upload source
            // This fixes existing items that were created before media_items support
            if (empty($payload['media_items']) && $contentItem->source_type === 'media_upload') {
                $fromSource = $this->resolveFromMediaUpload((int) $contentItem->source_ref);
                if (!empty($fromSource['media_items'])) {
                    $payload['media_items'] = $fromSource['media_items'];
                }
            }

            return [
                'caption' => $payload['caption'] ?? '',
                'media_urls' => $payload['media_urls'] ?? [],
                'media_items' => $payload['media_items'] ?? [],
                'hashtags' => $payload['hashtags'] ?? [],
            ];
        }

        if ($contentItem->source_type === 'media_upload') {
            return $this->resolveFromMediaUpload((int) $contentItem->source_ref);
        }

        return [
            'caption' => '',
            'media_urls' => [],
            'hashtags' => [],
        ];
    }

    protected function resolveFromMediaUpload(int $mediaUploadId): array
    {
        $mediaUpload = MediaUpload::with('storageFile')->find($mediaUploadId);
        if (!$mediaUpload || !$mediaUpload->storageFile) {
            return ['caption' => '', 'media_urls' => [], 'hashtags' => []];
        }

        $storageFile = $mediaUpload->storageFile;
        $mediaUrl = $storageFile->url ?? $this->getUrlFromDriver($storageFile);

        $hashtags = $mediaUpload->hashtags ?? [];
        if (is_string($hashtags)) {
            $hashtags = array_filter(array_map('trim', explode(' ', $hashtags)));
        }

        $mediaItems = [];
        if ($mediaUrl) {
            $mimeType = $storageFile->mime_type ?? '';
            $isVideo = str_starts_with($mimeType, 'video/');
            $mediaItems[] = [
                'url' => $mediaUrl,
                'mime_type' => $mimeType,
                'is_video' => $isVideo,
            ];
        }

        return [
            'caption' => $mediaUpload->social_caption ?? $mediaUpload->title ?? '',
            'media_urls' => $mediaUrl ? [$mediaUrl] : [],
            'media_items' => $mediaItems,
            'hashtags' => is_array($hashtags) ? $hashtags : [],
        ];
    }

    protected function getUrlFromDriver(StorageFile $storageFile): ?string
    {
        try {
            $driver = StorageDriverFactory::make($storageFile->driver);
            return $driver->url($storageFile->path);
        } catch (\Throwable $e) {
            return null;
        }
    }
}
