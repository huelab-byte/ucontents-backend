<?php

declare(strict_types=1);

namespace Modules\VideoOverlay\Actions;

use Modules\VideoOverlay\Models\VideoOverlay;
use Modules\StorageManagement\Factories\StorageDriverFactory;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class DeleteVideoOverlayAction
{
    /**
     * Delete video overlay with all associated data (storage file)
     */
    public function execute(VideoOverlay $videoOverlay, bool $forceDelete = false): bool
    {
        return DB::transaction(function () use ($videoOverlay, $forceDelete) {
            try {
                $videoOverlayId = $videoOverlay->id;
                
                // Delete storage file
                $this->deleteStorageFile($videoOverlay);
                
                // Delete database record
                if ($forceDelete) {
                    $videoOverlay->forceDelete();
                } else {
                    $videoOverlay->delete();
                }
                
                Log::info('Video overlay deleted successfully', ['video_overlay_id' => $videoOverlayId]);
                
                return true;
            } catch (\Exception $e) {
                Log::error('Failed to delete video overlay', [
                    'video_overlay_id' => $videoOverlay->id,
                    'error' => $e->getMessage(),
                ]);
                throw $e;
            }
        });
    }

    /**
     * Delete storage file
     */
    private function deleteStorageFile(VideoOverlay $videoOverlay): void
    {
        $storageFile = $videoOverlay->storageFile;
        
        if (!$storageFile) {
            Log::debug('No storage file associated with video overlay', ['video_overlay_id' => $videoOverlay->id]);
            return;
        }

        try {
            // Get the storage driver using static factory method
            $driver = StorageDriverFactory::make($storageFile->driver);
            
            // Delete the physical file
            if ($driver->exists($storageFile->path)) {
                $driver->delete($storageFile->path);
                Log::debug('Storage file deleted', [
                    'video_overlay_id' => $videoOverlay->id,
                    'path' => $storageFile->path,
                ]);
            }
            
            // Delete the storage file record
            $storageFile->forceDelete();
            
        } catch (\Exception $e) {
            Log::error('Failed to delete storage file', [
                'video_overlay_id' => $videoOverlay->id,
                'storage_file_id' => $storageFile->id,
                'path' => $storageFile->path,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
