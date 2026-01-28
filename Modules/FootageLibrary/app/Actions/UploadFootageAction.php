<?php

declare(strict_types=1);

namespace Modules\FootageLibrary\Actions;

use Illuminate\Http\UploadedFile;
use Modules\StorageManagement\Services\FileUploadService;
use Modules\FootageLibrary\Models\Footage;
use Modules\FootageLibrary\Services\VideoProcessingService;
use Modules\FootageLibrary\Services\MetadataGenerationService;
use Modules\FootageLibrary\Jobs\ProcessFootageMetadataJob;
use Illuminate\Support\Facades\Log;

class UploadFootageAction
{
    public function __construct(
        private FileUploadService $fileUploadService,
        private VideoProcessingService $videoService,
        private MetadataGenerationService $metadataService
    ) {}

    /**
     * Upload a single footage file
     * 
     * Processing modes based on metadataSource:
     * - 'title': Queue job for AI metadata from title + vector storage
     * - 'frames': Queue job for AI metadata from frames + vector storage
     * - 'none': Skip queue entirely, extract basic video properties only (fastest)
     * 
     * @param bool $processAsync If true (default), process metadata in background queue.
     *                           If false, process synchronously (for backwards compatibility).
     */
    public function execute(
        UploadedFile $file,
        ?int $folderId = null,
        ?string $title = null,
        string $metadataSource = 'title',
        ?int $userId = null,
        bool $processAsync = true
    ): Footage {
        $userId = $userId ?? auth()->id();
        $title = $title ?? pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);

        try {
            // Step 1: Upload file to storage (fast operation)
            $storagePath = $this->buildStoragePath($folderId);
            $storageFile = $this->fileUploadService->upload($file, $storagePath, null);

            // Step 2: Determine processing mode
            // 'none' = skip queue entirely (no AI metadata, no vector indexing)
            $skipQueue = ($metadataSource === 'none');
            
            // Step 3: Create footage record
            $footage = Footage::create([
                'storage_file_id' => $storageFile->id,
                'folder_id' => $folderId,
                'title' => $title,
                'metadata' => [
                    'processing_started_at' => now()->toIso8601String(),
                    'metadata_source' => $metadataSource,
                ],
                'user_id' => $userId,
                'status' => $skipQueue ? 'ready' : ($processAsync ? 'processing' : 'ready'),
            ]);

            Log::info('Footage uploaded', [
                'footage_id' => $footage->id,
                'title' => $title,
                'metadata_source' => $metadataSource,
                'skip_queue' => $skipQueue,
            ]);

            // Step 4: Process based on mode
            if ($skipQueue) {
                // 'none' mode: Extract only basic video properties (fast, ~1 second)
                // No AI metadata generation, no vector storage
                $this->extractBasicProperties($footage);
                
                Log::info('Skipped queue - basic properties only', [
                    'footage_id' => $footage->id,
                ]);
            } elseif ($processAsync) {
                // 'title' or 'frames' mode: Dispatch queue job for full processing
                ProcessFootageMetadataJob::dispatch($footage->id, $metadataSource);
                
                Log::info('Dispatched ProcessFootageMetadataJob', [
                    'footage_id' => $footage->id,
                    'metadata_source' => $metadataSource,
                ]);
            } else {
                // Sync mode (backwards compatible)
                $this->processMetadataSync($footage, $metadataSource);
            }

            return $footage;
        } catch (\Exception $e) {
            Log::error('Failed to upload footage', [
                'file' => $file->getClientOriginalName(),
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Extract only basic video properties (no AI metadata, no vector storage)
     * This is used for 'none' metadata source - fastest upload mode
     */
    private function extractBasicProperties(Footage $footage): void
    {
        $storageFile = $footage->storageFile;
        $videoPath = null;

        try {
            $videoPath = $storageFile->getLocalPath();
            $properties = $this->videoService->getVideoProperties($videoPath);

            // Update with basic properties only - no AI metadata
            $footage->update([
                'metadata' => [
                    'duration' => $properties['duration'],
                    'resolution' => [
                        'width' => $properties['width'],
                        'height' => $properties['height'],
                    ],
                    'fps' => $properties['fps'],
                    'format' => $properties['format'],
                    'orientation' => $properties['orientation'],
                    'description' => "Video: {$footage->title}",
                    'tags' => [],
                    'ai_metadata_source' => 'none',
                ],
                'status' => 'ready',
            ]);
        } catch (\Exception $e) {
            Log::warning('Failed to extract video properties', [
                'footage_id' => $footage->id,
                'error' => $e->getMessage(),
            ]);
            
            // Still mark as ready even if properties extraction fails
            $footage->update([
                'metadata' => [
                    'description' => "Video: {$footage->title}",
                    'tags' => [],
                    'ai_metadata_source' => 'none',
                    'properties_error' => $e->getMessage(),
                ],
                'status' => 'ready',
            ]);
        } finally {
            if ($videoPath && $storageFile) {
                $storageFile->cleanupLocalPath($videoPath);
            }
        }
    }

    /**
     * Process metadata synchronously (backwards compatible)
     */
    private function processMetadataSync(Footage $footage, string $metadataSource): void
    {
        $storageFile = $footage->storageFile;
        $videoPath = null;

        try {
            // Get video properties
            $videoPath = $storageFile->getLocalPath();
            $properties = $this->videoService->getVideoProperties($videoPath);

            // Generate metadata
            $metadata = $this->generateMetadata($metadataSource, $videoPath, $footage->title, $footage->user_id);
            
            // Merge with video properties
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

            // Update footage
            $footage->update([
                'metadata' => $metadata,
                'status' => 'ready',
            ]);

            // Store embedding
            if ($metadataSource !== 'none') {
                app(\Modules\FootageLibrary\Actions\StoreEmbeddingAction::class)->execute($footage);
            }
        } finally {
            if ($videoPath && $storageFile) {
                $storageFile->cleanupLocalPath($videoPath);
            }
        }
    }

    /**
     * Generate metadata based on source
     */
    private function generateMetadata(string $source, string $videoPath, string $title, ?int $userId): array
    {
        return match ($source) {
            'frames' => $this->metadataService->generateFromFrames($videoPath, $title, $userId),
            'title' => $this->metadataService->generateFromTitle($title, $userId),
            default => [
                'description' => "Video: {$title}",
                'tags' => [],
                'orientation' => 'horizontal',
                'ai_metadata_source' => 'manual',
            ],
        };
    }

    /**
     * Build storage path for footage
     */
    private function buildStoragePath(?int $folderId): string
    {
        $basePath = 'footage-library';
        
        if ($folderId) {
            $folder = \Modules\FootageLibrary\Models\FootageFolder::find($folderId);
            if ($folder && $folder->path) {
                $basePath .= '/' . $folder->path;
            }
        }
        
        return $basePath;
    }
}
