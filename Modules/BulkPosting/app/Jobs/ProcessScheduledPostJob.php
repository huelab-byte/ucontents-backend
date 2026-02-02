<?php

declare(strict_types=1);

namespace Modules\BulkPosting\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;
use Modules\BulkPosting\Models\BulkPostingCampaign;
use Modules\BulkPosting\Models\BulkPostingContentItem;
use Modules\BulkPosting\Models\BulkPostingCampaignLog;
use Modules\BulkPosting\Services\ContentResolverService;
use Modules\BulkPosting\Services\PostingService;
use Modules\SocialConnection\Models\SocialConnectionChannel;
use Modules\SocialConnection\Models\SocialConnectionGroup;

class ProcessScheduledPostJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Number of times the job may be attempted
     */
    public int $tries = 3;

    /**
     * Backoff time in seconds between retries
     */
    public int $backoff = 60;

    /**
     * Job timeout in seconds
     */
    public int $timeout = 300; // 5 minutes for video uploads

    /**
     * Maximum number of unhandled exceptions to allow before failing
     */
    public int $maxExceptions = 3;

    public function __construct(
        public BulkPostingContentItem $contentItem
    ) {}

    /**
     * Calculate the number of seconds to wait before retrying the job
     * Uses exponential backoff: 60s, 120s, 240s
     */
    public function backoff(): array
    {
        return [60, 120, 240];
    }

    /**
     * Determine the time at which the job should timeout
     */
    public function retryUntil(): \DateTime
    {
        return now()->addMinutes(30);
    }

    public function handle(
        ContentResolverService $contentResolver,
        PostingService $postingService
    ): void {
        $startTime = microtime(true);

        try {
            $contentItem = $this->contentItem->fresh(['campaign.connections']);
            if (! $contentItem || ! $contentItem->campaign) {
                Log::error('BulkPosting: Content item or campaign not found', [
                    'content_item_id' => $this->contentItem->id,
                    'attempt' => $this->attempts(),
                ]);
                return;
            }

            $campaign = $contentItem->campaign;

            // Check if campaign is still running
            if ($campaign->status !== 'running') {
                Log::info('BulkPosting: Campaign no longer running, skipping', [
                    'campaign_id' => $campaign->id,
                    'campaign_status' => $campaign->status,
                    'content_item_id' => $contentItem->id,
                ]);
                // Reset to pending so it can be picked up if campaign resumes
                if ($contentItem->status === 'scheduled') {
                    $contentItem->update(['status' => 'pending', 'scheduled_at' => null]);
                }
                return;
            }

            $channels = $this->resolveChannels($campaign);
            if (empty($channels)) {
                $this->handleNoChannels($contentItem, $campaign);
                return;
            }

            // Resolve content payload
            $payload = $contentResolver->resolvePayload($contentItem);

            // Validate payload has required content
            if (empty($payload['caption']) && empty($payload['media_urls'])) {
                $this->handleEmptyPayload($contentItem, $campaign);
                return;
            }

            // Log posting attempt
            Log::info('BulkPosting: Starting post to channels', [
                'content_item_id' => $contentItem->id,
                'campaign_id' => $campaign->id,
                'channel_count' => count($channels),
                'has_media' => !empty($payload['media_urls']),
                'attempt' => $this->attempts(),
            ]);

            // Post to all channels
            $results = $postingService->postToChannels($contentItem, $channels, $payload);

            // Process results (pass channels for provider info)
            $this->processResults($contentItem, $campaign, $results, $channels);

            $duration = round(microtime(true) - $startTime, 2);
            Log::info('BulkPosting: Post job completed', [
                'content_item_id' => $contentItem->id,
                'duration_seconds' => $duration,
            ]);

        } catch (Throwable $e) {
            $this->handleException($e);
        }
    }

    /**
     * Handle no valid channels scenario
     */
    protected function handleNoChannels(BulkPostingContentItem $contentItem, BulkPostingCampaign $campaign): void
    {
        $contentItem->update([
            'status' => 'failed',
            'error_message' => 'No valid channels to post to. Ensure channels are active and connected.',
        ]);

        BulkPostingCampaignLog::create([
            'bulk_posting_campaign_id' => $campaign->id,
            'bulk_posting_content_item_id' => $contentItem->id,
            'event_type' => 'post_failed',
            'payload' => [
                'error' => 'No valid channels',
                'error_code' => 'NO_CHANNELS',
            ],
        ]);

        Log::warning('BulkPosting: No valid channels for posting', [
            'content_item_id' => $contentItem->id,
            'campaign_id' => $campaign->id,
        ]);
    }

    /**
     * Handle empty payload scenario
     */
    protected function handleEmptyPayload(BulkPostingContentItem $contentItem, BulkPostingCampaign $campaign): void
    {
        $contentItem->update([
            'status' => 'skipped',
            'error_message' => 'Content has no caption or media to post',
        ]);

        BulkPostingCampaignLog::create([
            'bulk_posting_campaign_id' => $campaign->id,
            'bulk_posting_content_item_id' => $contentItem->id,
            'event_type' => 'post_skipped',
            'payload' => [
                'reason' => 'Empty payload',
                'error_code' => 'EMPTY_PAYLOAD',
            ],
        ]);

        Log::info('BulkPosting: Skipping empty content item', [
            'content_item_id' => $contentItem->id,
        ]);
    }

    /**
     * Process posting results and update content item status
     * 
     * @param BulkPostingContentItem $contentItem
     * @param BulkPostingCampaign $campaign
     * @param array $results
     * @param array $channels Array of SocialConnectionChannel objects
     */
    protected function processResults(BulkPostingContentItem $contentItem, BulkPostingCampaign $campaign, array $results, array $channels = []): void
    {
        // Build channel info map for quick lookup
        $channelInfoMap = [];
        foreach ($channels as $channel) {
            $channelInfoMap[$channel->id] = [
                'provider' => $channel->provider,
                'type' => $channel->type,
                'name' => $channel->name,
            ];
        }

        $networkResults = [];
        $successCount = 0;
        $failureCount = 0;
        $errors = [];

        foreach ($results as $channelId => $result) {
            $channelInfo = $channelInfoMap[$channelId] ?? ['provider' => 'unknown', 'type' => 'unknown', 'name' => 'Unknown'];
            
            $networkResults[$channelId] = [
                'provider' => $channelInfo['provider'],
                'type' => $channelInfo['type'],
                'name' => $channelInfo['name'],
                'success' => $result['success'],
                'external_post_id' => $result['external_post_id'] ?? null,
                'error' => $result['error'] ?? null,
                'error_code' => $result['error_code'] ?? null,
            ];

            if ($result['success']) {
                $successCount++;
            } else {
                $failureCount++;
                $errors[$channelId] = [
                    'error' => $result['error'] ?? 'Unknown error',
                    'error_code' => $result['error_code'] ?? null,
                ];
            }
        }

        $totalChannels = count($results);

        // Determine overall status
        if ($successCount === $totalChannels) {
            // All succeeded
            $this->markAsPublished($contentItem, $campaign, $networkResults);
        } elseif ($successCount > 0) {
            // Partial success - mark as published but log failures
            $this->markAsPartialSuccess($contentItem, $campaign, $networkResults, $errors);
        } else {
            // All failed
            $this->markAsFailed($contentItem, $campaign, $networkResults, $errors);
        }
    }

    /**
     * Mark content item as successfully published
     * 
     * @param BulkPostingContentItem $contentItem
     * @param BulkPostingCampaign $campaign
     * @param array $networkResults Array of channel results with provider info
     */
    protected function markAsPublished(BulkPostingContentItem $contentItem, BulkPostingCampaign $campaign, array $networkResults): void
    {
        $contentItem->update([
            'status' => 'published',
            'published_at' => now(),
            'external_post_ids' => $networkResults,
            'error_message' => null,
        ]);

        BulkPostingCampaignLog::create([
            'bulk_posting_campaign_id' => $campaign->id,
            'bulk_posting_content_item_id' => $contentItem->id,
            'event_type' => 'post_published',
            'payload' => [
                'network_results' => $networkResults,
                'channel_count' => count($networkResults),
            ],
        ]);

        Log::info('BulkPosting: Content published successfully', [
            'content_item_id' => $contentItem->id,
            'network_results' => $networkResults,
        ]);
    }

    /**
     * Mark content item as partially successful
     * 
     * @param BulkPostingContentItem $contentItem
     * @param BulkPostingCampaign $campaign
     * @param array $networkResults Array of channel results with provider info
     * @param array $errors Array of errors by channel ID
     */
    protected function markAsPartialSuccess(BulkPostingContentItem $contentItem, BulkPostingCampaign $campaign, array $networkResults, array $errors): void
    {
        $errorSummary = implode('; ', array_map(
            fn ($channelId, $err) => "Channel {$channelId}: {$err['error']}",
            array_keys($errors),
            $errors
        ));

        $successCount = count(array_filter($networkResults, fn($r) => $r['success'] ?? false));

        $contentItem->update([
            'status' => 'published',
            'published_at' => now(),
            'external_post_ids' => $networkResults,
            'error_message' => "Partial success. Failures: {$errorSummary}",
        ]);

        BulkPostingCampaignLog::create([
            'bulk_posting_campaign_id' => $campaign->id,
            'bulk_posting_content_item_id' => $contentItem->id,
            'event_type' => 'post_published',
            'payload' => [
                'network_results' => $networkResults,
                'partial_success' => true,
                'success_count' => $successCount,
                'failure_count' => count($errors),
                'errors' => $errors,
            ],
        ]);

        Log::warning('BulkPosting: Content published with some failures', [
            'content_item_id' => $contentItem->id,
            'success_count' => $successCount,
            'failure_count' => count($errors),
            'errors' => $errors,
        ]);
    }

    /**
     * Mark content item as failed
     * 
     * @param BulkPostingContentItem $contentItem
     * @param BulkPostingCampaign $campaign
     * @param array $networkResults Array of channel results with provider info
     * @param array $errors Array of errors by channel ID
     */
    protected function markAsFailed(BulkPostingContentItem $contentItem, BulkPostingCampaign $campaign, array $networkResults, array $errors): void
    {
        $errorSummary = implode('; ', array_map(
            fn ($channelId, $err) => "Channel {$channelId}: {$err['error']}",
            array_keys($errors),
            $errors
        ));

        // Check if this is a retryable error
        $isRetryable = $this->isRetryableError($errors);

        if ($isRetryable && $this->attempts() < $this->tries) {
            // Let the job retry
            Log::info('BulkPosting: Post failed, will retry', [
                'content_item_id' => $contentItem->id,
                'attempt' => $this->attempts(),
                'max_attempts' => $this->tries,
                'errors' => $errors,
            ]);
            throw new \RuntimeException("Posting failed (attempt {$this->attempts()}): {$errorSummary}");
        }

        $contentItem->update([
            'status' => 'failed',
            'error_message' => $errorSummary,
            'external_post_ids' => $networkResults,
        ]);

        BulkPostingCampaignLog::create([
            'bulk_posting_campaign_id' => $campaign->id,
            'bulk_posting_content_item_id' => $contentItem->id,
            'event_type' => 'post_failed',
            'payload' => [
                'network_results' => $networkResults,
                'errors' => $errors,
                'attempts' => $this->attempts(),
            ],
        ]);

        Log::error('BulkPosting: Content posting failed', [
            'content_item_id' => $contentItem->id,
            'errors' => $errors,
            'attempts' => $this->attempts(),
        ]);
    }

    /**
     * Check if the errors are retryable (rate limits, temporary failures)
     */
    protected function isRetryableError(array $errors): bool
    {
        $retryableCodes = [
            'RATE_LIMIT',
            'TIMEOUT',
            'TEMPORARY_ERROR',
            'SERVICE_UNAVAILABLE',
            'NETWORK_ERROR',
            'VIDEO_PROCESSING_FAILED', // TikTok/IG video processing might need retry
        ];

        foreach ($errors as $error) {
            $code = $error['error_code'] ?? '';
            if (in_array($code, $retryableCodes, true)) {
                return true;
            }

            // Check for rate limit in error message
            $message = strtolower($error['error'] ?? '');
            if (str_contains($message, 'rate limit') || str_contains($message, 'too many requests')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Handle job exception
     */
    protected function handleException(Throwable $e): void
    {
        Log::error('BulkPosting: Post job exception', [
            'content_item_id' => $this->contentItem->id,
            'attempt' => $this->attempts(),
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);

        // If this is the last attempt, mark as failed
        if ($this->attempts() >= $this->tries) {
            $this->markContentItemFailed($e->getMessage());
        }

        throw $e;
    }

    /**
     * Handle job failure after all retries exhausted
     */
    public function failed(?Throwable $e): void
    {
        $errorMessage = $e ? $e->getMessage() : 'Unknown error after all retries';

        Log::error('BulkPosting: ProcessScheduledPostJob failed permanently', [
            'content_item_id' => $this->contentItem->id ?? null,
            'error' => $errorMessage,
            'attempts' => $this->attempts(),
        ]);

        $this->markContentItemFailed($errorMessage);
    }

    /**
     * Mark content item as failed
     */
    protected function markContentItemFailed(string $message): void
    {
        try {
            $item = $this->contentItem->fresh();
            if ($item && $item->status !== 'published') {
                $item->update([
                    'status' => 'failed',
                    'error_message' => substr($message, 0, 1000), // Limit error message length
                ]);

                BulkPostingCampaignLog::create([
                    'bulk_posting_campaign_id' => $item->bulk_posting_campaign_id,
                    'bulk_posting_content_item_id' => $item->id,
                    'event_type' => 'post_failed',
                    'payload' => [
                        'error' => $message,
                        'final_failure' => true,
                        'attempts' => $this->attempts(),
                    ],
                ]);
            }
        } catch (Throwable $e) {
            Log::error('BulkPosting: Could not mark content item as failed', [
                'content_item_id' => $this->contentItem->id ?? null,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Resolve channels from campaign connections
     */
    protected function resolveChannels(BulkPostingCampaign $campaign): array
    {
        $channelIds = [];
        $connections = $campaign->connections;

        foreach ($connections as $conn) {
            if ($conn->connection_type === 'channel') {
                $channelIds[(int) $conn->connection_id] = true;
            } else {
                $group = SocialConnectionGroup::with('channels')->find($conn->connection_id);
                if ($group) {
                    foreach ($group->channels as $ch) {
                        $channelIds[$ch->id] = true;
                    }
                }
            }
        }

        if (empty($channelIds)) {
            return [];
        }

        return SocialConnectionChannel::whereIn('id', array_keys($channelIds))
            ->where('is_active', true)
            ->get()
            ->all();
    }
}
