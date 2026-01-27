<?php

declare(strict_types=1);

namespace Modules\ImageLibrary\Actions;

use Illuminate\Http\UploadedFile;
use Modules\StorageManagement\Services\FileUploadService;
use Modules\ImageLibrary\Models\Image;
use Modules\ImageLibrary\Services\ImageProcessingService;
use Illuminate\Support\Facades\Log;

class UploadImageAction
{
    public function __construct(
        private FileUploadService $fileUploadService,
        private ImageProcessingService $imageService
    ) {}

    /**
     * Upload a single image file
     */
    public function execute(
        UploadedFile $file,
        ?int $folderId = null,
        ?string $title = null,
        ?int $userId = null
    ): Image {
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

            // Create image record
            $image = Image::create([
                'storage_file_id' => $storageFile->id,
                'folder_id' => $folderId,
                'title' => $title,
                'metadata' => $metadata,
                'user_id' => $userId,
                'status' => 'ready',
            ]);

            return $image;
        } catch (\Exception $e) {
            Log::error('Failed to upload image', [
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
     * Build storage path for image
     */
    private function buildStoragePath(?int $folderId): string
    {
        $basePath = 'image-library';
        
        if ($folderId) {
            $folder = \Modules\ImageLibrary\Models\ImageFolder::find($folderId);
            if ($folder && $folder->path) {
                $basePath .= '/' . $folder->path;
            }
        }
        
        return $basePath;
    }
}
