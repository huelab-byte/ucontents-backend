<?php

declare(strict_types=1);

namespace Modules\MediaUpload\Console;

use Illuminate\Console\Command;
use Modules\MediaUpload\Jobs\ProcessMediaUploadJob;
use Modules\MediaUpload\Models\MediaUploadQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;

class DispatchMediaUploadsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'media:dispatch-uploads {--daemon : Run as a daemon}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fairly dispatches pending media uploads to the queue using a round-robin strategy per user.';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $this->info('Starting media upload dispatcher...');

        $queueName = config('mediaupload.queue_name', 'media_uploads');
        $limit = (int) config('mediaupload.dispatcher.queue_limit', 100);
        $sleepMs = (int) config('mediaupload.dispatcher.loop_sleep_ms', 1000); // 1 second
        $daemon = $this->option('daemon');

        do {
            try {
                $this->processBatch($queueName, $limit);
            } catch (\Throwable $e) {
                Log::error('Media dispatcher failed: ' . $e->getMessage());
                $this->error($e->getMessage());
                sleep(5); // Sleep longer on error
            }

            if ($daemon) {
                usleep($sleepMs * 1000);
            }
        } while ($daemon);
    }

    private function processBatch(string $queueName, int $limit): void
    {
        // Check current queue size in Redis
        // We use the Queue facade to be driver-agnostic, though we expect Redis/Database
        try {
            $size = Queue::connection('redis')->size($queueName);
        } catch (\Exception $e) {
            // Fallback if redis connection not named 'redis' or fails
            $size = Queue::size($queueName);
        }

        if ($size >= $limit) {
            // Queue is full, wait for workers to process
            return;
        }

        // How many slots available?
        $slots = $limit - $size;

        // Find users with pending uploads
        // We want to pick 1 job from each user, round robin.
        // Efficient query: Get distinct user_ids from pending jobs, up to slot limit
        $userIds = MediaUploadQueue::where('status', 'pending')
            ->select('user_id')
            ->distinct()
            ->limit($slots)
            ->pluck('user_id')
            ->toArray();

        if (empty($userIds)) {
            return;
        }

        foreach ($userIds as $userId) {
            // Get oldest pending job for this user
            $job = MediaUploadQueue::where('user_id', $userId)
                ->where('status', 'pending')
                ->orderBy('created_at', 'asc')
                ->first();

            if ($job) {
                // Dispatch to the specific queue
                // Update status first to prevent double dispatch
                $job->update(['status' => 'queued']);

                ProcessMediaUploadJob::dispatch($job->id)->onQueue($queueName);

                $this->info("Dispatched job {$job->id} for user {$userId}");
            }
        }
    }
}
