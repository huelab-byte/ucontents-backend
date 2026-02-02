<?php

declare(strict_types=1);

namespace Modules\BulkPosting\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Modules\BulkPosting\Jobs\ProcessScheduledPostJob;
use Modules\BulkPosting\Models\BulkPostingCampaign;
use Modules\BulkPosting\Models\BulkPostingCampaignLog;
use Modules\BulkPosting\Models\BulkPostingContentItem;

class ScheduleService
{
    public function __construct() {}

    public function processDueCampaigns(): int
    {
        $campaigns = BulkPostingCampaign::where('status', 'running')
            ->with(['contentItems', 'connections'])
            ->get();

        $dispatched = 0;

        foreach ($campaigns as $campaign) {
            $dispatched += $this->processCampaign($campaign);
        }

        return $dispatched;
    }

    protected function processCampaign(BulkPostingCampaign $campaign): int
    {
        $dispatched = 0;

        // Check if campaign should be marked as completed
        if ($this->shouldMarkCompleted($campaign)) {
            $this->markCampaignCompleted($campaign);
            return 0;
        }

        if ($this->isNewPostDue($campaign)) {
            $contentItem = $this->pickNextPendingContent($campaign);
            if ($contentItem) {
                $this->scheduleContentForPost($contentItem);
                $campaign->update(['last_post_at' => now()]);
                $dispatched++;
            }
        }

        if ($campaign->repost_enabled && $this->isRepostDue($campaign)) {
            $contentItem = $this->pickNextRepostContent($campaign);
            if ($contentItem) {
                $contentItem->increment('republish_count');
                $contentItem->update(['status' => 'scheduled', 'scheduled_at' => now()]);
                $this->dispatchPostJob($contentItem);
                $campaign->update(['last_repost_at' => now()]);
                $dispatched++;
            }
        }

        // Check again after processing (in case this was the last item)
        if ($dispatched === 0 && $this->shouldMarkCompleted($campaign->fresh())) {
            $this->markCampaignCompleted($campaign);
        }

        return $dispatched;
    }

    /**
     * Check if campaign should be marked as completed
     */
    protected function shouldMarkCompleted(BulkPostingCampaign $campaign): bool
    {
        // Count pending and scheduled items
        $pendingCount = BulkPostingContentItem::where('bulk_posting_campaign_id', $campaign->id)
            ->whereIn('status', ['pending', 'scheduled'])
            ->count();

        // If there are still pending/scheduled items, not completed
        if ($pendingCount > 0) {
            return false;
        }

        // If repost is enabled, check if there are items that can still be reposted
        if ($campaign->repost_enabled) {
            $maxCount = $campaign->repost_max_count ?? 1;
            $repostableCount = BulkPostingContentItem::where('bulk_posting_campaign_id', $campaign->id)
                ->where('status', 'published')
                ->where('republish_count', '<', $maxCount)
                ->count();

            if ($repostableCount > 0) {
                return false;
            }
        }

        // All items are either published (with max reposts), failed, or skipped
        return true;
    }

    /**
     * Mark campaign as completed
     */
    protected function markCampaignCompleted(BulkPostingCampaign $campaign): void
    {
        $campaign->update(['status' => 'completed']);

        // Get summary stats
        $stats = [
            'published' => BulkPostingContentItem::where('bulk_posting_campaign_id', $campaign->id)
                ->where('status', 'published')
                ->count(),
            'failed' => BulkPostingContentItem::where('bulk_posting_campaign_id', $campaign->id)
                ->where('status', 'failed')
                ->count(),
            'skipped' => BulkPostingContentItem::where('bulk_posting_campaign_id', $campaign->id)
                ->where('status', 'skipped')
                ->count(),
        ];

        // Log completion event
        BulkPostingCampaignLog::create([
            'bulk_posting_campaign_id' => $campaign->id,
            'bulk_posting_content_item_id' => null,
            'event_type' => 'campaign_completed',
            'payload' => $stats,
        ]);

        Log::info('BulkPosting: Campaign completed', [
            'campaign_id' => $campaign->id,
            'stats' => $stats,
        ]);
    }

    protected function isNewPostDue(BulkPostingCampaign $campaign): bool
    {
        $lastPost = $campaign->last_post_at ?? $campaign->started_at;
        if (! $lastPost) {
            return true;
        }

        $nextDue = $this->computeNextDueTime($lastPost, $campaign->schedule_condition, $campaign->schedule_interval);
        return now()->gte($nextDue);
    }

    protected function isRepostDue(BulkPostingCampaign $campaign): bool
    {
        $lastRepost = $campaign->last_repost_at ?? $campaign->started_at;
        if (! $lastRepost || ! $campaign->repost_condition || $campaign->repost_interval < 1) {
            return false;
        }

        $nextDue = $this->computeNextDueTime($lastRepost, $campaign->repost_condition, $campaign->repost_interval);
        return now()->gte($nextDue);
    }

    protected function computeNextDueTime(Carbon $from, string $condition, int $interval): Carbon
    {
        return match ($condition) {
            'minute' => $from->copy()->addMinutes($interval),
            'hourly' => $from->copy()->addHours($interval),
            'daily' => $from->copy()->addDays($interval),
            'weekly' => $from->copy()->addWeeks($interval),
            'monthly' => $from->copy()->addMonths($interval),
            default => $from->copy()->addDays($interval),
        };
    }

    /** Picks next item to post: pending first, or "stuck" scheduled (dispatched but job never ran/finished) */
    protected function pickNextPendingContent(BulkPostingCampaign $campaign): ?BulkPostingContentItem
    {
        $item = BulkPostingContentItem::where('bulk_posting_campaign_id', $campaign->id)
            ->where('status', 'pending')
            ->orderBy('id')
            ->first();

        if ($item) {
            return $item;
        }

        // Stuck "scheduled": job was dispatched but queue worker didn't run it (or job failed).
        // Redispatch if scheduled_at is older than 2 minutes.
        $stuckThreshold = now()->subMinutes(2);

        return BulkPostingContentItem::where('bulk_posting_campaign_id', $campaign->id)
            ->where('status', 'scheduled')
            ->where('scheduled_at', '<', $stuckThreshold)
            ->orderBy('id')
            ->first();
    }

    protected function pickNextRepostContent(BulkPostingCampaign $campaign): ?BulkPostingContentItem
    {
        $maxCount = $campaign->repost_max_count ?? 1;

        return BulkPostingContentItem::where('bulk_posting_campaign_id', $campaign->id)
            ->where('status', 'published')
            ->where('republish_count', '<', $maxCount)
            ->orderBy('published_at')
            ->first();
    }

    protected function scheduleContentForPost(BulkPostingContentItem $contentItem): void
    {
        $contentItem->update([
            'status' => 'scheduled',
            'scheduled_at' => now(),
        ]);
        $this->dispatchPostJob($contentItem);
    }

    protected function dispatchPostJob(BulkPostingContentItem $contentItem): void
    {
        ProcessScheduledPostJob::dispatch($contentItem);
    }
}
