<?php

declare(strict_types=1);

namespace Modules\ImageOverlay\Actions;

use Modules\ImageOverlay\Models\ImageOverlay;
use Modules\StorageManagement\Factories\StorageDriverFactory;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class DeleteImageOverlayAction
{
    /**
     * Delete image overlay with all associated data (storage file)
     */
    public function execute(ImageOverlay $imageOverlay, bool $forceDelete = false): bool
    {
        return DB::transaction(function () use ($imageOverlay, $forceDelete) {
            try {
                $imageOverlayId = $imageOverlay->id;
                
                // 1. Delete storage file
                $this->deleteStorageFile($imageOverlay);
                
                // 2. Delete database record
                if ($forceDelete) {
                    $imageOverlay->forceDelete();
                } else {
                    $imageOverlay->delete();
                }
                
                Log::info('Image overlay deleted successfully', ['image_overlay_id' => $imageOverlayId]);
                
                return true;
            } catch (\Exception $e) {
                Log::error('Failed to delete image overlay', [
                    'image_overlay_id' => $imageOverlay->id,
                    'error' => $e->getMessage(),
                ]);
                throw $e;
            }
        });
    }

    /**
     * Delete storage file
     */
    private function deleteStorageFile(ImageOverlay $imageOverlay): void
    {
        $storageFile = $imageOverlay->storageFile;
        
        if (!$storageFile) {
            Log::debug('No storage file associated with image overlay', ['image_overlay_id' => $imageOverlay->id]);
            return;
        }

        try {
            // Get the storage driver using static factory method
            $driver = StorageDriverFactory::make($storageFile->driver);
            
            // Delete the physical file
            if ($driver->exists($storageFile->path)) {
                $driver->delete($storageFile->path);
                Log::debug('Storage file deleted', [
                    'image_overlay_id' => $imageOverlay->id,
                    'path' => $storageFile->path,
                ]);
            }
            
            // Delete the storage file record
            $storageFile->forceDelete();
            
        } catch (\Exception $e) {
            Log::error('Failed to delete storage file', [
                'image_overlay_id' => $imageOverlay->id,
                'storage_file_id' => $storageFile->id,
                'path' => $storageFile->path,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
