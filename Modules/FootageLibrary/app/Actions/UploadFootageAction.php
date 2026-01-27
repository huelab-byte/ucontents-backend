<?php

declare(strict_types=1);

namespace Modules\FootageLibrary\Actions;

use Illuminate\Http\UploadedFile;
use Modules\StorageManagement\Services\FileUploadService;
use Modules\FootageLibrary\Models\Footage;
use Modules\FootageLibrary\Services\VideoProcessingService;
use Modules\FootageLibrary\Services\MetadataGenerationService;
use Modules\FootageLibrary\Jobs\StoreEmbeddingJob;
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
     */
    public function execute(
        UploadedFile $file,
        ?int $folderId = null,
        ?string $title = null,
        string $metadataSource = 'title',
        ?int $userId = null
    ): Footage {
        $videoPath = null;
        $storageFile = null;
        
        try {
            $userId = $userId ?? auth()->id();
            $title = $title ?? pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);

            // Upload file using StorageManagement
            $storagePath = $this->buildStoragePath($folderId);
            $storageFile = $this->fileUploadService->upload($file, $storagePath, null);

            // Get video properties using StorageManagement's local path helper
            // This may download the file to temp if using remote storage (S3)
            $videoPath = $storageFile->getLocalPath();
            $properties = $this->videoService->getVideoProperties($videoPath);

            // Generate metadata
            $metadata = $this->generateMetadata($metadataSource, $videoPath, $title, $userId);
            
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

            // Create footage record
            $footage = Footage::create([
                'storage_file_id' => $storageFile->id,
                'folder_id' => $folderId,
                'title' => $title,
                'metadata' => $metadata,
                'user_id' => $userId,
                'status' => 'ready',
            ]);

            // Only store embedding in Qdrant if AI metadata was generated
            // Skip for 'none' metadata source as there's no meaningful content to embed
            if ($metadataSource !== 'none') {
                StoreEmbeddingJob::dispatch($footage->id);
                Log::info('Dispatched StoreEmbeddingJob', ['footage_id' => $footage->id]);
            } else {
                Log::info('Skipped embedding storage - no AI metadata', ['footage_id' => $footage->id]);
            }

            return $footage;
        } catch (\Exception $e) {
            Log::error('Failed to upload footage', [
                'file' => $file->getClientOriginalName(),
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
