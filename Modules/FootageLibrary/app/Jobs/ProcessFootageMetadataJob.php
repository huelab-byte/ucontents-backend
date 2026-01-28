<?php

declare(strict_types=1);

namespace Modules\FootageLibrary\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\FootageLibrary\Models\Footage;
use Modules\FootageLibrary\Services\VideoProcessingService;
use Modules\FootageLibrary\Services\MetadataGenerationService;
use Modules\FootageLibrary\Actions\StoreEmbeddingAction;
use Modules\StorageManagement\Models\StorageFile;
use Illuminate\Support\Facades\Log;

/**
 * Process footage metadata in the background
 * 
 * This job handles:
 * 1. Extracting video properties (duration, resolution, fps)
 * 2. Generating AI metadata (from frames or title)
 * 3. Storing embeddings in vector database
 */
class ProcessFootageMetadataJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Number of times the job may be attempted
     */
    public int $tries = 3;

    /**
     * Number of seconds to wait before retrying
     */
    public int $backoff = 30;

    /**
     * Timeout in seconds
     */
    public int $timeout = 300; // 5 minutes for video processing

    public function __construct(
        public int $footageId,
        public string $metadataSource = 'title'
    ) {}

    public function handle(
        VideoProcessingService $videoService,
        MetadataGenerationService $metadataService,
        StoreEmbeddingAction $storeEmbeddingAction
    ): void {
        $footage = Footage::with('storageFile')->find($this->footageId);
        
        if (!$footage) {
            Log::error('Footage not found for metadata processing', ['footage_id' => $this->footageId]);
            return;
        }

        if (!$footage->storageFile) {
            Log::error('Storage file not found for footage', ['footage_id' => $this->footageId]);
            $this->markFootageFailed($footage, 'Storage file not found');
            return;
        }

        $localPath = null;
        
        try {
            Log::info('Starting metadata processing', [
                'footage_id' => $this->footageId,
                'metadata_source' => $this->metadataSource,
            ]);

            // Get local path for processing (downloads from S3 if needed)
            $localPath = $footage->storageFile->getLocalPath();

            // Step 1: Extract video properties
            $properties = $videoService->getVideoProperties($localPath);
            
            Log::debug('Video properties extracted', [
                'footage_id' => $this->footageId,
                'properties' => $properties,
            ]);

            // Step 2: Generate AI metadata based on source
            $metadata = $this->generateMetadata(
                $metadataService,
                $localPath,
                $footage->title,
                $footage->user_id
            );

            // Step 3: Merge video properties with AI metadata
            $metadata = array_merge($metadata, [
                'duration' => $properties['duration'],
                'resolution' => [
                    'width' => $properties['width'],
                    'height' => $properties['height'],
                ],
                'fps' => $properties['fps'],
                'format' => $properties['format'],
                'orientation' => $properties['orientation'],
            ]);

            // Step 4: Update footage record
            $footage->update([
                'metadata' => $metadata,
                'status' => 'ready',
            ]);

            Log::info('Footage metadata updated', ['footage_id' => $this->footageId]);

            // Step 5: Store embedding in vector database (if AI metadata was generated)
            if ($this->metadataSource !== 'none') {
                try {
                    $storeEmbeddingAction->execute($footage);
                    Log::info('Embedding stored successfully', ['footage_id' => $this->footageId]);
                } catch (\Exception $e) {
                    // Don't fail the job for embedding errors - footage is still usable
                    Log::warning('Failed to store embedding, but footage is ready', [
                        'footage_id' => $this->footageId,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

        } catch (\Exception $e) {
            Log::error('Metadata processing failed', [
                'footage_id' => $this->footageId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->markFootageFailed($footage, $e->getMessage());
            
            throw $e; // Re-throw to trigger retry
        } finally {
            // Clean up temp file
            if ($localPath && $footage->storageFile) {
                $footage->storageFile->cleanupLocalPath($localPath);
            }
        }
    }

    /**
     * Generate metadata based on source
     */
    private function generateMetadata(
        MetadataGenerationService $metadataService,
        string $videoPath,
        string $title,
        ?int $userId
    ): array {
        return match ($this->metadataSource) {
            'frames' => $metadataService->generateFromFrames($videoPath, $title, $userId),
            'title' => $metadataService->generateFromTitle($title, $userId),
            default => [
                'description' => "Video: {$title}",
                'tags' => [],
                'orientation' => 'horizontal',
                'ai_metadata_source' => 'manual',
            ],
        };
    }

    /**
     * Mark footage as failed
     */
    private function markFootageFailed(Footage $footage, string $error): void
    {
        $footage->update([
            'status' => 'failed',
            'metadata' => array_merge($footage->metadata ?? [], [
                'processing_error' => $error,
                'failed_at' => now()->toIso8601String(),
            ]),
        ]);
    }

    /**
     * Handle job failure
     */
    public function failed(\Throwable $exception): void
    {
        $footage = Footage::find($this->footageId);
        
        if ($footage) {
            $this->markFootageFailed($footage, $exception->getMessage());
        }

        Log::error('ProcessFootageMetadataJob permanently failed', [
            'footage_id' => $this->footageId,
            'error' => $exception->getMessage(),
        ]);
    }
}
