<?php

declare(strict_types=1);

namespace Modules\BgmLibrary\Actions;

use Illuminate\Http\UploadedFile;
use Modules\StorageManagement\Services\FileUploadService;
use Modules\BgmLibrary\Models\Bgm;
use Modules\BgmLibrary\Services\BgmProcessingService;
use Illuminate\Support\Facades\Log;

class UploadBgmAction
{
    public function __construct(
        private FileUploadService $fileUploadService,
        private BgmProcessingService $bgmService
    ) {}

    /**
     * Upload a single BGM file
     */
    public function execute(
        UploadedFile $file,
        ?int $folderId = null,
        ?string $title = null,
        ?int $userId = null
    ): Bgm {
        $bgmPath = null;
        $storageFile = null;
        
        try {
            $userId = $userId ?? auth()->id();
            $title = $title ?? pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);

            // Upload file using StorageManagement
            $storagePath = $this->buildStoragePath($folderId);
            $storageFile = $this->fileUploadService->upload($file, $storagePath, null);

            // Get BGM properties using StorageManagement's local path helper
            $bgmPath = $storageFile->getLocalPath();
            $properties = $this->bgmService->getBgmProperties($bgmPath);

            // Build metadata from title and BGM properties
            $metadata = [
                'description' => '',
                'tags' => [],
                'duration' => $properties['duration'],
                'bitrate' => $properties['bitrate'],
                'sample_rate' => $properties['sample_rate'],
                'channels' => $properties['channels'],
                'format' => $properties['format'],
            ];

            // Create BGM record
            $bgm = Bgm::create([
                'storage_file_id' => $storageFile->id,
                'folder_id' => $folderId,
                'title' => $title,
                'metadata' => $metadata,
                'user_id' => $userId,
                'status' => 'ready',
            ]);

            return $bgm;
        } catch (\Exception $e) {
            Log::error('Failed to upload BGM', [
                'file' => $file->getClientOriginalName(),
                'error' => $e->getMessage(),
            ]);
            throw $e;
        } finally {
            // Cleanup temporary file if remote storage was used
            if ($bgmPath && $storageFile) {
                $storageFile->cleanupLocalPath($bgmPath);
            }
        }
    }

    /**
     * Build storage path for BGM
     */
    private function buildStoragePath(?int $folderId): string
    {
        $basePath = 'bgm-library';
        
        if ($folderId) {
            $folder = \Modules\BgmLibrary\Models\BgmFolder::findOrFail($folderId);
            if ($folder->path) {
                $basePath .= '/' . $folder->path;
            }
        }
        
        return $basePath;
    }
}
