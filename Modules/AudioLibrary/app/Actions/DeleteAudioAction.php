<?php

declare(strict_types=1);

namespace Modules\AudioLibrary\Actions;

use Modules\AudioLibrary\Models\Audio;
use Modules\StorageManagement\Factories\StorageDriverFactory;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class DeleteAudioAction
{
    /**
     * Delete audio with all associated data (storage file)
     */
    public function execute(Audio $audio, bool $forceDelete = false): bool
    {
        return DB::transaction(function () use ($audio, $forceDelete) {
            try {
                $audioId = $audio->id;
                
                // 1. Delete storage file
                $this->deleteStorageFile($audio);
                
                // 2. Delete database record
                if ($forceDelete) {
                    $audio->forceDelete();
                } else {
                    $audio->delete();
                }
                
                Log::info('Audio deleted successfully', ['audio_id' => $audioId]);
                
                return true;
            } catch (\Exception $e) {
                Log::error('Failed to delete audio', [
                    'audio_id' => $audio->id,
                    'error' => $e->getMessage(),
                ]);
                throw $e;
            }
        });
    }

    /**
     * Delete storage file
     */
    private function deleteStorageFile(Audio $audio): void
    {
        $storageFile = $audio->storageFile;
        
        if (!$storageFile) {
            Log::debug('No storage file associated with audio', ['audio_id' => $audio->id]);
            return;
        }

        try {
            // Get the storage driver using static factory method
            $driver = StorageDriverFactory::make($storageFile->driver);
            
            // Delete the physical file
            if ($driver->exists($storageFile->path)) {
                $driver->delete($storageFile->path);
                Log::debug('Storage file deleted', [
                    'audio_id' => $audio->id,
                    'path' => $storageFile->path,
                ]);
            }
            
            // Delete the storage file record
            $storageFile->forceDelete();
            
        } catch (\Exception $e) {
            Log::error('Failed to delete storage file', [
                'audio_id' => $audio->id,
                'storage_file_id' => $storageFile->id,
                'path' => $storageFile->path,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
