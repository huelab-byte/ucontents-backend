<?php

declare(strict_types=1);

namespace Modules\FootageLibrary\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\FootageLibrary\Models\Footage;
use Modules\FootageLibrary\Actions\ExtractFramesAction;
use Modules\FootageLibrary\Actions\GenerateMetadataFromFramesAction;
use Modules\FootageLibrary\Jobs\StoreEmbeddingJob;
use Illuminate\Support\Facades\Log;

class ExtractFramesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $footageId
    ) {}

    public function handle(
        ExtractFramesAction $extractFramesAction,
        GenerateMetadataFromFramesAction $generateMetadataAction
    ): void {
        $footage = Footage::find($this->footageId);
        
        if (!$footage) {
            Log::error('Footage not found for frame extraction', ['footage_id' => $this->footageId]);
            return;
        }

        $videoPath = null;
        $storageFile = null;

        try {
            $footage->update(['status' => 'processing']);

            // Get video file path using StorageManagement (supports local and remote storage)
            $storageFile = $footage->storageFile;
            if (!$storageFile) {
                throw new \Exception('Storage file not found');
            }

            // Get local path - downloads to temp if using remote storage (S3)
            $videoPath = $storageFile->getLocalPath();

            // Extract and merge frames
            $extractionResult = $extractFramesAction->execute($videoPath);
            $mergedFramePath = $extractionResult['merged_frame_path'];

            try {
                // Generate metadata from frames
                $metadata = $generateMetadataAction->execute($mergedFramePath, $footage->title, $footage->user_id);
                
                // Update footage metadata
                $existingMetadata = $footage->metadata;
                $updatedMetadata = array_merge($existingMetadata, $metadata);
                
                $footage->update([
                    'metadata' => $updatedMetadata,
                    'status' => 'ready',
                ]);

                // Dispatch job to store embedding
                StoreEmbeddingJob::dispatch($footage->id);
            } finally {
                // Cleanup merged frame
                if (file_exists($mergedFramePath)) {
                    unlink($mergedFramePath);
                }
            }
        } catch (\Exception $e) {
            $footage->update(['status' => 'failed']);
            
            Log::error('Frame extraction job failed', [
                'footage_id' => $this->footageId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        } finally {
            // Cleanup temporary file if remote storage was used
            if ($videoPath && $storageFile) {
                $storageFile->cleanupLocalPath($videoPath);
            }
        }
    }
}
