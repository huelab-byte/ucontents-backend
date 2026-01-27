<?php

declare(strict_types=1);

namespace Modules\ImageLibrary\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use Modules\ImageLibrary\Models\ImageUploadQueue;
use Modules\ImageLibrary\Actions\UploadImageAction;

class ProcessImageUploadJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $maxExceptions = 3;

    public function __construct(
        public int $queueId
    ) {}

    public function handle(UploadImageAction $uploadAction): void
    {
        $queueItem = ImageUploadQueue::find($this->queueId);

        if (!$queueItem) {
            Log::warning('Queue item not found', ['queue_id' => $this->queueId]);
            return;
        }

        try {
            $queueItem->markAsProcessing();

            // Get the temp file
            $tempPath = Storage::path($queueItem->file_path);
            
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
            $image = $uploadAction->execute(
                $file,
                $queueItem->folder_id,
                pathinfo($queueItem->file_name, PATHINFO_FILENAME),
                $queueItem->user_id
            );

            $queueItem->updateProgress(90);

            // Mark as completed
            $queueItem->markAsCompleted($image->id);

            // Cleanup temp file
            Storage::delete($queueItem->file_path);

            Log::info('Image upload processed successfully', [
                'queue_id' => $this->queueId,
                'image_id' => $image->id,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to process image upload', [
                'queue_id' => $this->queueId,
                'error' => $e->getMessage(),
            ]);

            $queueItem->markAsFailed($e->getMessage());

            // Cleanup temp file on failure
            if (isset($queueItem->file_path)) {
                Storage::delete($queueItem->file_path);
            }

            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Image upload job failed permanently', [
            'queue_id' => $this->queueId,
            'error' => $exception->getMessage(),
        ]);

        $queueItem = ImageUploadQueue::find($this->queueId);
        if ($queueItem) {
            $queueItem->markAsFailed($exception->getMessage());
        }
    }
}
