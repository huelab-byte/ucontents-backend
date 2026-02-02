<?php

declare(strict_types=1);

namespace Modules\BulkPosting\Http\Controllers\Api\V1\Customer;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\Http\Controllers\Api\BaseApiController;
use Modules\BulkPosting\Actions\CreateCampaignAction;
use Modules\BulkPosting\Actions\DeleteCampaignAction;
use Modules\BulkPosting\Actions\PauseCampaignAction;
use Modules\BulkPosting\Actions\ResumeCampaignAction;
use Modules\BulkPosting\Actions\StartCampaignAction;
use Modules\BulkPosting\Actions\SyncCampaignAction;
use Modules\BulkPosting\Actions\UpdateCampaignAction;
use Modules\BulkPosting\DTOs\CreateCampaignDTO;
use Modules\BulkPosting\DTOs\UpdateCampaignDTO;
use Modules\BulkPosting\Http\Requests\StoreCampaignRequest;
use Modules\BulkPosting\Http\Requests\UpdateCampaignRequest;
use Modules\BulkPosting\Http\Resources\CampaignResource;
use Modules\BulkPosting\Models\BulkPostingCampaign;

class CampaignController extends BaseApiController
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', BulkPostingCampaign::class);

        $campaigns = BulkPostingCampaign::query()
            ->where('user_id', $request->user()->id)
            ->with(['connections', 'brandLogo'])
            ->withCount(['contentItems as total_post_count'])
            ->orderByDesc('created_at')
            ->paginate((int) $request->get('per_page', 20));

        $items = $campaigns->getCollection()->map(function (BulkPostingCampaign $campaign) {
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
                'contentSource' => $this->resolveContentSourceForCampaign($campaign),
                'totalPost' => $campaign->total_post_count ?? 0,
                'postedAmount' => $publishedCount,
                'remainingContent' => $remainingCount,
                'startedDate' => ($campaign->started_at ?? $campaign->created_at)?->toISOString() ?? '',
                'status' => $campaign->status,
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Campaigns retrieved successfully',
            'data' => $items,
            'pagination' => [
                'total' => $campaigns->total(),
                'per_page' => $campaigns->perPage(),
                'current_page' => $campaigns->currentPage(),
                'last_page' => $campaigns->lastPage(),
            ],
        ]);
    }

    public function store(StoreCampaignRequest $request, CreateCampaignAction $action): JsonResponse
    {
        $this->authorize('create', BulkPostingCampaign::class);

        $data = $request->validated();
        $data['connections'] = $data['connections'] ?? ['channels' => [], 'groups' => []];
        $data['content_source_config'] = $data['content_source_config'] ?? [];

        $dto = CreateCampaignDTO::fromArray($data);
        $campaign = $action->execute($request->user(), $dto);

        $campaign->load(['connections', 'brandLogo']);

        return $this->success(new CampaignResource($campaign), 'Campaign created successfully', 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $campaign = BulkPostingCampaign::with(['connections', 'brandLogo'])->findOrFail($id);
        $this->authorize('view', $campaign);

        return $this->success(new CampaignResource($campaign), 'Campaign retrieved successfully');
    }

    public function update(UpdateCampaignRequest $request, UpdateCampaignAction $action, int $id): JsonResponse
    {
        $campaign = BulkPostingCampaign::findOrFail($id);
        $this->authorize('update', $campaign);

        $data = $request->validated();
        if (isset($data['connections'])) {
            $data['connections'] = $data['connections'];
        }
        if (isset($data['content_source_config'])) {
            $data['content_source_config'] = $data['content_source_config'];
        }

        $dto = UpdateCampaignDTO::fromArray($data);
        $updated = $action->execute($campaign, $dto);
        $updated->load(['connections', 'brandLogo']);

        return $this->success(new CampaignResource($updated), 'Campaign updated successfully');
    }

    public function destroy(DeleteCampaignAction $action, int $id): JsonResponse
    {
        $campaign = BulkPostingCampaign::findOrFail($id);
        $this->authorize('delete', $campaign);

        $action->execute($campaign);

        return $this->success(null, 'Campaign deleted successfully');
    }

    public function pause(Request $request, PauseCampaignAction $action, int $id): JsonResponse
    {
        $campaign = BulkPostingCampaign::findOrFail($id);
        $this->authorize('update', $campaign);

        $updated = $action->execute($campaign);
        $updated->load(['connections', 'brandLogo']);

        return $this->success(new CampaignResource($updated), 'Campaign paused successfully');
    }

    public function resume(Request $request, ResumeCampaignAction $action, int $id): JsonResponse
    {
        $campaign = BulkPostingCampaign::findOrFail($id);
        $this->authorize('update', $campaign);

        $updated = $action->execute($campaign);
        $updated->load(['connections', 'brandLogo']);

        return $this->success(new CampaignResource($updated), 'Campaign resumed successfully');
    }

    public function start(Request $request, StartCampaignAction $action, int $id): JsonResponse
    {
        $campaign = BulkPostingCampaign::findOrFail($id);
        $this->authorize('update', $campaign);

        $updated = $action->execute($campaign);
        $updated->load(['connections', 'brandLogo']);

        return $this->success(new CampaignResource($updated), 'Campaign started successfully');
    }

    public function contentItems(Request $request, int $id): JsonResponse
    {
        $campaign = BulkPostingCampaign::findOrFail($id);
        $this->authorize('view', $campaign);

        // Get campaign's connected channels for fallback network display
        $campaignChannels = $this->resolveCampaignChannels($campaign);

        $items = $campaign->contentItems()
            ->orderByDesc('scheduled_at')
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->paginate((int) $request->get('per_page', 50));

        $data = $items->getCollection()->map(function ($item) use ($campaignChannels) {
            // Use published_at for published items, scheduled_at for scheduled, or created_at as fallback
            $displayDate = $item->published_at ?? $item->scheduled_at ?? $item->created_at;
            
            // Determine content type from media URLs
            $mediaUrls = $item->payload['media_urls'] ?? [];
            $type = 'text';
            if (!empty($mediaUrls)) {
                $firstUrl = $mediaUrls[0] ?? '';
                $extension = strtolower(pathinfo(parse_url($firstUrl, PHP_URL_PATH) ?? '', PATHINFO_EXTENSION));
                $videoExtensions = ['mp4', 'mov', 'avi', 'wmv', 'flv', 'webm', 'mkv', 'm4v'];
                $type = in_array($extension, $videoExtensions) ? 'video' : 'image';
            }

            // Parse network results from external_post_ids
            $networkResults = [];
            $externalPostIds = $item->external_post_ids ?? [];
            $hasNewFormat = false;
            
            if (is_array($externalPostIds)) {
                foreach ($externalPostIds as $channelId => $result) {
                    if (is_array($result) && isset($result['provider'])) {
                        // New format with provider info
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
                        'success' => $isPublished, // Assume success if published, fail if failed
                        'externalPostId' => null,
                        'error' => $isFailed ? ($item->error_message ?? 'Unknown error') : null,
                    ];
                }
            }
            
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
        });

        return response()->json([
            'success' => true,
            'message' => 'Content items retrieved',
            'data' => $data,
            'pagination' => [
                'total' => $items->total(),
                'per_page' => $items->perPage(),
                'current_page' => $items->currentPage(),
                'last_page' => $items->lastPage(),
            ],
        ]);
    }

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
    protected function resolveCampaignChannels(BulkPostingCampaign $campaign): array
    {
        $channels = [];
        
        foreach ($campaign->connections as $connection) {
            if ($connection->connection_type === 'channel') {
                $channel = \Modules\SocialConnection\Models\SocialConnectionChannel::find($connection->connection_id);
                if ($channel) {
                    $channels[$channel->id] = [
                        'id' => $channel->id,
                        'provider' => $channel->provider,
                        'type' => $channel->type,
                        'name' => $channel->name,
                    ];
                }
            } elseif ($connection->connection_type === 'group') {
                $group = \Modules\SocialConnection\Models\SocialConnectionGroup::with('channels')->find($connection->connection_id);
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

    public function sync(Request $request, SyncCampaignAction $action, int $id): JsonResponse
    {
        $campaign = BulkPostingCampaign::findOrFail($id);
        $this->authorize('update', $campaign);

        $result = $action->execute($campaign);

        if (isset($result['error'])) {
            return $this->error($result['error'], 400);
        }

        return $this->success([
            'added' => $result['added'],
            'skipped' => $result['skipped'],
            'total' => $result['total'],
        ], "Campaign synced successfully. Added {$result['added']} new items.");
    }

    public function downloadSampleCsv(): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $samplePath = storage_path('app/public/templates/bulk-posting-sample.csv');

        if (!file_exists($samplePath)) {
            // Create the sample file if it doesn't exist
            $this->createSampleCsvFile($samplePath);
        }

        return response()->download($samplePath, 'bulk-posting-sample.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }

    protected function createSampleCsvFile(string $path): void
    {
        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        // CSV columns matching MediaUpload fields:
        // - caption: The social media post text (maps to social_caption in MediaUpload)
        // - media_url: URL to the image or video file
        // - hashtags: Space-separated hashtags (e.g., "#tag1 #tag2 #tag3")
        $content = "caption,media_url,hashtags\n";
        $content .= "\"Discover the secrets of productivity! Watch our latest video to transform your daily routine into a success story. Click now to learn more!\",https://example.com/videos/productivity-tips.mp4,\"#Productivity #Success #DailyRoutine #LifeHacks #Motivation\"\n";
        $content .= "\"Step into a world of creativity! Our new collection is here and it's absolutely stunning. Which piece is your favorite?\",https://example.com/images/new-collection.jpg,\"#NewCollection #Creative #Fashion #Style #TrendAlert\"\n";
        $content .= "\"Behind the scenes of our latest photoshoot! See how the magic happens. Swipe to see more exclusive content!\",https://example.com/images/bts-photoshoot.jpg,\"#BehindTheScenes #Photoshoot #Exclusive #ContentCreation\"\n";
        $content .= "\"Big announcement coming your way! Stay tuned for something exciting. Drop a comment if you're ready!\",https://example.com/images/announcement-teaser.jpg,\"#Announcement #ComingSoon #StayTuned #Excited\"\n";
        $content .= "\"Transform your space with these simple tips! Watch the full tutorial on our channel. Link in bio!\",https://example.com/videos/home-decor-tips.mp4,\"#HomeDecor #InteriorDesign #DIY #HomeTips #Transformation\"\n";

        file_put_contents($path, $content);
    }

    /**
     * Resolve content source to display names for a campaign
     * - For media_upload: returns "Media Upload / FolderName" format
     * - For csv_file: returns the CSV filename
     */
    protected function resolveContentSourceForCampaign(BulkPostingCampaign $campaign): array
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
            // For CSV imports, show the filename from config
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
