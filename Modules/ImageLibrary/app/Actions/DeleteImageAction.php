<?php

declare(strict_types=1);

namespace Modules\ImageLibrary\Actions;

use Modules\ImageLibrary\Models\Image;
use Modules\StorageManagement\Factories\StorageDriverFactory;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class DeleteImageAction
{
    /**
     * Delete image with all associated data (storage file)
     */
    public function execute(Image $image, bool $forceDelete = false): bool
    {
        return DB::transaction(function () use ($image, $forceDelete) {
            try {
                $imageId = $image->id;
                
                // 1. Delete storage file
                $this->deleteStorageFile($image);
                
                // 2. Delete database record
                if ($forceDelete) {
                    $image->forceDelete();
                } else {
                    $image->delete();
                }
                
                Log::info('Image deleted successfully', ['image_id' => $imageId]);
                
                return true;
            } catch (\Exception $e) {
                Log::error('Failed to delete image', [
                    'image_id' => $image->id,
                    'error' => $e->getMessage(),
                ]);
                throw $e;
            }
        });
    }

    /**
     * Delete storage file
     */
    private function deleteStorageFile(Image $image): void
    {
        $storageFile = $image->storageFile;
        
        if (!$storageFile) {
            Log::debug('No storage file associated with image', ['image_id' => $image->id]);
            return;
        }

        try {
            // Get the storage driver using static factory method
            $driver = StorageDriverFactory::make($storageFile->driver);
            
            // Delete the physical file
            if ($driver->exists($storageFile->path)) {
                $driver->delete($storageFile->path);
                Log::debug('Storage file deleted', [
                    'image_id' => $image->id,
                    'path' => $storageFile->path,
                ]);
            }
            
            // Delete the storage file record
            $storageFile->forceDelete();
            
        } catch (\Exception $e) {
            Log::error('Failed to delete storage file', [
                'image_id' => $image->id,
                'storage_file_id' => $storageFile->id,
                'path' => $storageFile->path,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
