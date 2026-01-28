<?php

declare(strict_types=1);

namespace Modules\ImageOverlay\Actions;

use Illuminate\Http\UploadedFile;
use Modules\StorageManagement\Services\FileUploadService;
use Modules\ImageOverlay\Models\ImageOverlay;
use Modules\ImageOverlay\Services\ImageOverlayProcessingService;
use Illuminate\Support\Facades\Log;

class UploadImageOverlayAction
{
    public function __construct(
        private FileUploadService $fileUploadService,
        private ImageOverlayProcessingService $imageService
    ) {}

    /**
     * Upload a single image overlay file
     */
    public function execute(
        UploadedFile $file,
        ?int $folderId = null,
        ?string $title = null,
        ?int $userId = null
    ): ImageOverlay {
        $imagePath = null;
        $storageFile = null;
        
        try {
            $userId = $userId ?? auth()->id();
            $title = $title ?? pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);

            // Upload file using StorageManagement
            $storagePath = $this->buildStoragePath($folderId);
            $storageFile = $this->fileUploadService->upload($file, $storagePath, null);

            // Get image properties using StorageManagement's local path helper
            $imagePath = $storageFile->getLocalPath();
            $properties = $this->imageService->getImageProperties($imagePath);

            // Build metadata from title and image properties
            $metadata = [
                'description' => '',
                'tags' => [],
                'width' => $properties['width'],
                'height' => $properties['height'],
                'format' => $properties['format'],
                'file_size' => $properties['file_size'],
            ];

            // Create image overlay record
            $imageOverlay = ImageOverlay::create([
                'storage_file_id' => $storageFile->id,
                'folder_id' => $folderId,
                'title' => $title,
                'metadata' => $metadata,
                'user_id' => $userId,
                'status' => 'ready',
            ]);

            return $imageOverlay;
        } catch (\Exception $e) {
            Log::error('Failed to upload image overlay', [
                'file' => $file->getClientOriginalName(),
                'error' => $e->getMessage(),
            ]);
            throw $e;
        } finally {
            // Cleanup temporary file if remote storage was used
            if ($imagePath && $storageFile) {
                $storageFile->cleanupLocalPath($imagePath);
            }
        }
    }

    /**
     * Build storage path for image overlay
     */
    private function buildStoragePath(?int $folderId): string
    {
        $basePath = 'image-overlays';
        
        if ($folderId) {
            $folder = \Modules\ImageOverlay\Models\ImageOverlayFolder::findOrFail($folderId);
            if ($folder->path) {
                $basePath .= '/' . $folder->path;
            }
        }
        
        return $basePath;
    }
}
