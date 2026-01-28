<?php

declare(strict_types=1);

namespace Modules\VideoOverlay\Actions;

use Illuminate\Http\UploadedFile;
use Modules\StorageManagement\Services\FileUploadService;
use Modules\VideoOverlay\Models\VideoOverlay;
use Modules\VideoOverlay\Services\VideoProcessingService;
use Illuminate\Support\Facades\Log;

class UploadVideoOverlayAction
{
    public function __construct(
        private FileUploadService $fileUploadService,
        private VideoProcessingService $videoService
    ) {}

    /**
     * Upload a single video overlay file
     * Only extracts basic video properties - no AI metadata, no queue, no frame extraction
     */
    public function execute(
        UploadedFile $file,
        ?int $folderId = null,
        ?string $title = null,
        ?int $userId = null
    ): VideoOverlay {
        $userId = $userId ?? auth()->id();
        $title = $title ?? pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);

        try {
            // Step 1: Upload file to storage
            $storagePath = $this->buildStoragePath($folderId);
            $storageFile = $this->fileUploadService->upload($file, $storagePath, null);

            // Step 2: Create video overlay record
            $videoOverlay = VideoOverlay::create([
                'storage_file_id' => $storageFile->id,
                'folder_id' => $folderId,
                'title' => $title,
                'user_id' => $userId,
                'status' => 'pending',
            ]);

            Log::info('Video overlay uploaded', [
                'video_overlay_id' => $videoOverlay->id,
                'title' => $title,
            ]);

            // Step 3: Extract basic video properties
            $this->extractBasicProperties($videoOverlay);

            return $videoOverlay;
        } catch (\Exception $e) {
            Log::error('Failed to upload video overlay', [
                'file' => $file->getClientOriginalName(),
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Extract only basic video properties (no AI metadata, no frame extraction)
     */
    private function extractBasicProperties(VideoOverlay $videoOverlay): void
    {
        $storageFile = $videoOverlay->storageFile;
        $videoPath = null;

        try {
            $videoPath = $storageFile->getLocalPath();
            $properties = $this->videoService->getVideoProperties($videoPath);

            // Update with basic properties only
            $videoOverlay->update([
                'metadata' => [
                    'duration' => $properties['duration'],
                    'resolution' => [
                        'width' => $properties['width'],
                        'height' => $properties['height'],
                    ],
                    'fps' => $properties['fps'],
                    'format' => $properties['format'],
                    'orientation' => $properties['orientation'],
                ],
                'status' => 'ready',
            ]);
        } catch (\Exception $e) {
            Log::warning('Failed to extract video properties', [
                'video_overlay_id' => $videoOverlay->id,
                'error' => $e->getMessage(),
            ]);
            
            // Still mark as ready even if properties extraction fails
            $videoOverlay->update([
                'metadata' => [
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
     * Build storage path for video overlay
     */
    private function buildStoragePath(?int $folderId): string
    {
        $basePath = 'video-overlays';
        
        if ($folderId) {
            $folder = \Modules\VideoOverlay\Models\VideoOverlayFolder::findOrFail($folderId);
            if ($folder->path) {
                $basePath .= '/' . $folder->path;
            }
        }
        
        return $basePath;
    }
}
