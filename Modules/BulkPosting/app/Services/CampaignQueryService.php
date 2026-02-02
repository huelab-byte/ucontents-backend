<?php

declare(strict_types=1);

namespace Modules\BulkPosting\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;
use Illuminate\Support\Collection;
use Modules\BulkPosting\Models\BulkPostingCampaign;
use Modules\BulkPosting\Models\BulkPostingContentItem;
use Modules\SocialConnection\Models\SocialConnectionChannel;
use Modules\SocialConnection\Models\SocialConnectionGroup;

class CampaignQueryService
{
    /**
     * Get paginated campaigns for a user with computed display data
     */
    public function getCampaignsForUser(int $userId, int $perPage = 20): LengthAwarePaginator
    {
        $campaigns = BulkPostingCampaign::query()
            ->where('user_id', $userId)
            ->with(['connections', 'brandLogo'])
            ->withCount(['contentItems as total_post_count'])
            ->orderByDesc('created_at')
            ->paginate($perPage);

        $items = $campaigns->getCollection()->map(fn (BulkPostingCampaign $campaign) => $this->mapCampaignToListItem($campaign));

        return new Paginator(
            $items,
            $campaigns->total(),
            $campaigns->perPage(),
            $campaigns->currentPage(),
            ['path' => $campaigns->path()]
        );
    }

    /**
     * Map a campaign to list item display format
     */
    protected function mapCampaignToListItem(BulkPostingCampaign $campaign): array
    {
        $publishedCount = $campaign->contentItems()->where('status', 'published')->count();
        $remainingCount = $campaign->contentItems()->whereIn('status', ['pending', 'scheduled'])->count();

        return [
            'id' => (string) $campaign->id,
            'brand' => [
                'name' => $campaign->brand_name,
                'logo' => $campaign->brandLogo?->url ?? null,
                'projectName' => $campaign->project_name,
            ],
            'connections' => [
                'channels' => $campaign->connections->where('connection_type', 'channel')->pluck('connection_id')->map(fn ($id) => (int) $id)->values()->all(),
                'groups' => $campaign->connections->where('connection_type', 'group')->pluck('connection_id')->map(fn ($id) => (int) $id)->values()->all(),
            ],
            'contentSourceType' => $campaign->content_source_type,
            'contentSource' => $this->resolveContentSourceDisplay($campaign),
            'totalPost' => $campaign->total_post_count ?? 0,
            'postedAmount' => $publishedCount,
            'remainingContent' => $remainingCount,
            'startedDate' => ($campaign->started_at ?? $campaign->created_at)?->toISOString() ?? '',
            'status' => $campaign->status,
        ];
    }

    /**
     * Get paginated content items for a campaign with display data
     */
    public function getContentItemsForCampaign(BulkPostingCampaign $campaign, int $perPage = 50): LengthAwarePaginator
    {
        $campaignChannels = $this->resolveCampaignChannels($campaign);

        $items = $campaign->contentItems()
            ->orderByDesc('scheduled_at')
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->paginate($perPage);

        $data = $items->getCollection()->map(fn ($item) => $this->mapContentItemToDisplay($item, $campaignChannels));

        return new Paginator(
            $data,
            $items->total(),
            $items->perPage(),
            $items->currentPage(),
            ['path' => $items->path()]
        );
    }

    /**
     * Map a content item to display format
     */
    protected function mapContentItemToDisplay(BulkPostingContentItem $item, array $campaignChannels): array
    {
        $displayDate = $item->published_at ?? $item->scheduled_at ?? $item->created_at;
        $type = $this->determineContentType($item);
        $networkResults = $this->parseNetworkResults($item, $campaignChannels);

        return [
            'id' => (string) $item->id,
            'publishedAt' => $displayDate?->toISOString() ?? now()->toISOString(),
            'title' => $item->payload['caption'] ?? 'Content',
            'description' => '',
            'type' => $type,
            'platforms' => [], // Deprecated - use networkResults instead
            'networkResults' => $networkResults,
            'status' => $this->mapContentStatus($item->status),
            'contentText' => $item->payload['caption'] ?? '',
        ];
    }

    /**
     * Determine content type from media URLs
     */
    protected function determineContentType(BulkPostingContentItem $item): string
    {
        $mediaUrls = $item->payload['media_urls'] ?? [];
        
        if (empty($mediaUrls)) {
            return 'text';
        }

        $firstUrl = $mediaUrls[0] ?? '';
        $extension = strtolower(pathinfo(parse_url($firstUrl, PHP_URL_PATH) ?? '', PATHINFO_EXTENSION));
        $videoExtensions = ['mp4', 'mov', 'avi', 'wmv', 'flv', 'webm', 'mkv', 'm4v'];
        
        return in_array($extension, $videoExtensions) ? 'video' : 'image';
    }

    /**
     * Parse network results from external_post_ids
     */
    protected function parseNetworkResults(BulkPostingContentItem $item, array $campaignChannels): array
    {
        $networkResults = [];
        $externalPostIds = $item->external_post_ids ?? [];
        $hasNewFormat = false;

        if (is_array($externalPostIds)) {
            foreach ($externalPostIds as $channelId => $result) {
                if (is_array($result) && isset($result['provider'])) {
                    $hasNewFormat = true;
                    $networkResults[] = [
                        'channelId' => (string) $channelId,
                        'provider' => $result['provider'],
                        'type' => $result['type'] ?? 'unknown',
                        'name' => $result['name'] ?? '',
                        'success' => $result['success'] ?? false,
                        'externalPostId' => $result['external_post_id'] ?? null,
                        'error' => $result['error'] ?? null,
                    ];
                }
            }
        }

        // For old format or items without network results, use campaign channels
        if (!$hasNewFormat && !empty($campaignChannels)) {
            $isPublished = $item->status === 'published';
            $isFailed = $item->status === 'failed';

            foreach ($campaignChannels as $channel) {
                $networkResults[] = [
                    'channelId' => (string) $channel['id'],
                    'provider' => $channel['provider'],
                    'type' => $channel['type'],
                    'name' => $channel['name'],
                    'success' => $isPublished,
                    'externalPostId' => null,
                    'error' => $isFailed ? ($item->error_message ?? 'Unknown error') : null,
                ];
            }
        }

        return $networkResults;
    }

    /**
     * Map content status to display status
     */
    protected function mapContentStatus(string $status): string
    {
        return match ($status) {
            'published' => 'published',
            'scheduled' => 'scheduled',
            'failed' => 'error',
            default => 'scheduled',
        };
    }

    /**
     * Resolve all channels connected to a campaign (direct channels + group channels)
     */
    public function resolveCampaignChannels(BulkPostingCampaign $campaign): array
    {
        $channels = [];

        foreach ($campaign->connections as $connection) {
            if ($connection->connection_type === 'channel') {
                $channel = SocialConnectionChannel::find($connection->connection_id);
                if ($channel) {
                    $channels[$channel->id] = [
                        'id' => $channel->id,
                        'provider' => $channel->provider,
                        'type' => $channel->type,
                        'name' => $channel->name,
                    ];
                }
            } elseif ($connection->connection_type === 'group') {
                $group = SocialConnectionGroup::with('channels')->find($connection->connection_id);
                if ($group) {
                    foreach ($group->channels as $channel) {
                        $channels[$channel->id] = [
                            'id' => $channel->id,
                            'provider' => $channel->provider,
                            'type' => $channel->type,
                            'name' => $channel->name,
                        ];
                    }
                }
            }
        }

        return array_values($channels);
    }

    /**
     * Resolve content source to display names for a campaign
     * - For media_upload: returns "Media Upload / FolderName" format
     * - For csv_file: returns the CSV filename
     */
    public function resolveContentSourceDisplay(BulkPostingCampaign $campaign): array
    {
        $contentSource = [];

        if ($campaign->content_source_type === 'media_upload') {
            $folderIds = $campaign->content_source_config['folder_ids'] ?? [];
            if (!empty($folderIds) && class_exists(\Modules\MediaUpload\Models\MediaUploadFolder::class)) {
                $folders = \Modules\MediaUpload\Models\MediaUploadFolder::whereIn('id', $folderIds)
                    ->where('user_id', $campaign->user_id)
                    ->get(['id', 'name']);

                foreach ($folders as $folder) {
                    $contentSource[] = "Media Upload / {$folder->name}";
                }
            }
        } elseif ($campaign->content_source_type === 'csv_file') {
            $filename = $campaign->content_source_config['filename'] ?? $campaign->content_source_config['csv_filename'] ?? null;
            if ($filename) {
                $contentSource[] = $filename;
            } else {
                $contentSource[] = 'CSV Import';
            }
        }

        return $contentSource;
    }
}
