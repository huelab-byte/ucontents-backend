<?php

declare(strict_types=1);

namespace Modules\AudioLibrary\Actions;

use Illuminate\Http\UploadedFile;
use Modules\StorageManagement\Services\FileUploadService;
use Modules\AudioLibrary\Models\Audio;
use Modules\AudioLibrary\Services\AudioProcessingService;
use Illuminate\Support\Facades\Log;

class UploadAudioAction
{
    public function __construct(
        private FileUploadService $fileUploadService,
        private AudioProcessingService $audioService
    ) {}

    /**
     * Upload a single audio file
     */
    public function execute(
        UploadedFile $file,
        ?int $folderId = null,
        ?string $title = null,
        ?int $userId = null
    ): Audio {
        $audioPath = null;
        $storageFile = null;
        
        try {
            $userId = $userId ?? auth()->id();
            $title = $title ?? pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);

            // Upload file using StorageManagement
            $storagePath = $this->buildStoragePath($folderId);
            $storageFile = $this->fileUploadService->upload($file, $storagePath, null);

            // Get audio properties using StorageManagement's local path helper
            $audioPath = $storageFile->getLocalPath();
            $properties = $this->audioService->getAudioProperties($audioPath);

            // Build metadata from title and audio properties
            $metadata = [
                'description' => '',
                'tags' => [],
                'duration' => $properties['duration'],
                'bitrate' => $properties['bitrate'],
                'sample_rate' => $properties['sample_rate'],
                'channels' => $properties['channels'],
                'format' => $properties['format'],
            ];

            // Create audio record
            $audio = Audio::create([
                'storage_file_id' => $storageFile->id,
                'folder_id' => $folderId,
                'title' => $title,
                'metadata' => $metadata,
                'user_id' => $userId,
                'status' => 'ready',
            ]);

            return $audio;
        } catch (\Exception $e) {
            Log::error('Failed to upload audio', [
                'file' => $file->getClientOriginalName(),
                'error' => $e->getMessage(),
            ]);
            throw $e;
        } finally {
            // Cleanup temporary file if remote storage was used
            if ($audioPath && $storageFile) {
                $storageFile->cleanupLocalPath($audioPath);
            }
        }
    }

    /**
     * Build storage path for audio
     */
    private function buildStoragePath(?int $folderId): string
    {
        $basePath = 'audio-library';
        
        if ($folderId) {
            $folder = \Modules\AudioLibrary\Models\AudioFolder::find($folderId);
            if ($folder && $folder->path) {
                $basePath .= '/' . $folder->path;
            }
        }
        
        return $basePath;
    }
}
