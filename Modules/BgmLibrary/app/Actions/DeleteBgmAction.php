<?php

declare(strict_types=1);

namespace Modules\BgmLibrary\Actions;

use Modules\BgmLibrary\Models\Bgm;
use Modules\StorageManagement\Factories\StorageDriverFactory;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class DeleteBgmAction
{
    /**
     * Delete BGM with all associated data (storage file)
     */
    public function execute(Bgm $bgm, bool $forceDelete = false): bool
    {
        return DB::transaction(function () use ($bgm, $forceDelete) {
            try {
                $bgmId = $bgm->id;
                
                // 1. Delete storage file
                $this->deleteStorageFile($bgm);
                
                // 2. Delete database record
                if ($forceDelete) {
                    $bgm->forceDelete();
                } else {
                    $bgm->delete();
                }
                
                Log::info('BGM deleted successfully', ['bgm_id' => $bgmId]);
                
                return true;
            } catch (\Exception $e) {
                Log::error('Failed to delete BGM', [
                    'bgm_id' => $bgm->id,
                    'error' => $e->getMessage(),
                ]);
                throw $e;
            }
        });
    }

    /**
     * Delete storage file
     */
    private function deleteStorageFile(Bgm $bgm): void
    {
        $storageFile = $bgm->storageFile;
        
        if (!$storageFile) {
            Log::debug('No storage file associated with BGM', ['bgm_id' => $bgm->id]);
            return;
        }

        try {
            // Get the storage driver using static factory method
            $driver = StorageDriverFactory::make($storageFile->driver);
            
            // Delete the physical file
            if ($driver->exists($storageFile->path)) {
                $driver->delete($storageFile->path);
                Log::debug('Storage file deleted', [
                    'bgm_id' => $bgm->id,
                    'path' => $storageFile->path,
                ]);
            }
            
            // Delete the storage file record
            $storageFile->forceDelete();
            
        } catch (\Exception $e) {
            Log::error('Failed to delete storage file', [
                'bgm_id' => $bgm->id,
                'storage_file_id' => $storageFile->id,
                'path' => $storageFile->path,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
