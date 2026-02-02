<?php

declare(strict_types=1);

namespace Modules\StorageManagement\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\StorageManagement\Models\StorageUploadQueue;
use Modules\StorageManagement\Actions\UploadFileAction;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class ProcessFileUploadJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $queueId
    ) {}

    public function handle(UploadFileAction $uploadAction): void
    {
        $queueItem = StorageUploadQueue::find($this->queueId);
        
        if (!$queueItem) {
            Log::error('Upload queue item not found', ['queue_id' => $this->queueId]);
            return;
        }

        try {
            $queueItem->update([
                'status' => 'processing',
                'progress' => 10,
            ]);

            // Get file from temporary storage (local disk for queue temp files)
            if (!Storage::disk('local')->exists($queueItem->file_path)) {
                throw new \Exception('Temporary file not found');
            }

            // Read file content and create temporary file for upload
            $content = Storage::disk('local')->get($queueItem->file_path);
            $tempFile = tempnam(sys_get_temp_dir(), 'upload_');
            file_put_contents($tempFile, $content);
            
            // Create UploadedFile instance
            $file = new \Illuminate\Http\UploadedFile(
                $tempFile,
                $queueItem->file_name,
                $queueItem->mime_type,
                null,
                true
            );

            $queueItem->update(['progress' => 50]);

            // Upload file
            $metadata = $queueItem->metadata ?? [];
            $storageFile = $uploadAction->execute(
                $file,
                $metadata['path'] ?? null,
                $queueItem->user_id,
                $metadata['reference_type'] && $metadata['reference_id']
                    ? $metadata['reference_type']::find($metadata['reference_id'])
                    : null
            );

            // Clean up temporary files
            Storage::disk('local')->delete($queueItem->file_path);
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }

            $queueItem->update([
                'status' => 'completed',
                'progress' => 100,
                'storage_file_id' => $storageFile->id,
                'processed_at' => now(),
            ]);
        } catch (\Exception $e) {
            $queueItem->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'attempts' => $queueItem->attempts + 1,
            ]);

            Log::error('File upload job failed', [
                'queue_id' => $this->queueId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
