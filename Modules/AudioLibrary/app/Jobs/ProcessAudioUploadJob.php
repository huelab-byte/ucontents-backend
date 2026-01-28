<?php

declare(strict_types=1);

namespace Modules\AudioLibrary\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use Modules\AudioLibrary\Models\AudioUploadQueue;
use Modules\AudioLibrary\Actions\UploadAudioAction;

class ProcessAudioUploadJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $maxExceptions = 3;

    public function __construct(
        public int $queueId
    ) {}

    public function handle(UploadAudioAction $uploadAction): void
    {
        $queueItem = AudioUploadQueue::find($this->queueId);

        if (!$queueItem) {
            Log::warning('Queue item not found', ['queue_id' => $this->queueId]);
            return;
        }

        try {
            $queueItem->markAsProcessing();

            // Get the temp file from local disk (temp files are always on local disk)
            $tempPath = Storage::disk('local')->path($queueItem->file_path);
            
            if (!file_exists($tempPath)) {
                throw new \Exception('Temporary file not found');
            }

            // Create UploadedFile from temp file
            $file = new UploadedFile(
                $tempPath,
                $queueItem->file_name,
                $queueItem->mime_type,
                null,
                true
            );

            $queueItem->updateProgress(30);

            // Process upload
            $audio = $uploadAction->execute(
                $file,
                $queueItem->folder_id,
                pathinfo($queueItem->file_name, PATHINFO_FILENAME),
                $queueItem->user_id
            );

            $queueItem->updateProgress(90);

            // Mark as completed
            $queueItem->markAsCompleted($audio->id);

            // Cleanup temp file from local disk
            Storage::disk('local')->delete($queueItem->file_path);

            Log::info('Audio upload processed successfully', [
                'queue_id' => $this->queueId,
                'audio_id' => $audio->id,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to process audio upload', [
                'queue_id' => $this->queueId,
                'error' => $e->getMessage(),
            ]);

            $queueItem->markAsFailed($e->getMessage());

            // Cleanup temp file on failure from local disk
            if (isset($queueItem->file_path)) {
                Storage::disk('local')->delete($queueItem->file_path);
            }

            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Audio upload job failed permanently', [
            'queue_id' => $this->queueId,
            'error' => $exception->getMessage(),
        ]);

        $queueItem = AudioUploadQueue::find($this->queueId);
        if ($queueItem) {
            $queueItem->markAsFailed($exception->getMessage());
        }
    }
}
