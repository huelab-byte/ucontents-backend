<?php

declare(strict_types=1);

namespace Modules\FootageLibrary\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\FootageLibrary\Models\FootageUploadQueue;
use Modules\FootageLibrary\Actions\UploadFootageAction;
use Modules\FootageLibrary\Jobs\ExtractFramesJob;
use Modules\FootageLibrary\Jobs\GenerateMetadataJob;
use Modules\FootageLibrary\Jobs\StoreEmbeddingJob;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class ProcessFootageUploadJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $queueId
    ) {}

    public function handle(UploadFootageAction $uploadAction): void
    {
        $queueItem = FootageUploadQueue::find($this->queueId);
        
        if (!$queueItem) {
            Log::error('Upload queue item not found', ['queue_id' => $this->queueId]);
            return;
        }

        try {
            $queueItem->markAsProcessing();

            // Get file from temporary local storage (temp files are always on local disk)
            if (!Storage::disk('local')->exists($queueItem->file_path)) {
                throw new \Exception('Temporary file not found');
            }

            // Read file content and create temporary file for upload
            $content = Storage::disk('local')->get($queueItem->file_path);
            $tempFile = tempnam(sys_get_temp_dir(), 'footage_upload_');
            file_put_contents($tempFile, $content);
            
            // Create UploadedFile instance
            $file = new \Illuminate\Http\UploadedFile(
                $tempFile,
                $queueItem->file_name,
                $queueItem->mime_type,
                null,
                true
            );

            $queueItem->updateProgress(30);

            // Upload footage
            $title = pathinfo($queueItem->file_name, PATHINFO_FILENAME);
            $footage = $uploadAction->execute(
                $file,
                $queueItem->folder_id,
                $title,
                $queueItem->metadata_source,
                $queueItem->user_id
            );

            $queueItem->updateProgress(60);

            // Clean up temporary files from local disk
            Storage::disk('local')->delete($queueItem->file_path);
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }

            // Dispatch jobs for metadata generation and embedding storage
            if ($queueItem->metadata_source === 'frames') {
                ExtractFramesJob::dispatch($footage->id);
            } else {
                GenerateMetadataJob::dispatch($footage->id);
            }

            $queueItem->updateProgress(80);
            $queueItem->markAsCompleted($footage->id);
        } catch (\Exception $e) {
            $queueItem->markAsFailed($e->getMessage());

            Log::error('Footage upload job failed', [
                'queue_id' => $this->queueId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
