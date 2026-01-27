<?php

declare(strict_types=1);

namespace Modules\FootageLibrary\Actions;

use Modules\FootageLibrary\Models\Footage;
use Modules\FootageLibrary\Integrations\QdrantService;
use Modules\StorageManagement\Factories\StorageDriverFactory;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class DeleteFootageAction
{
    public function __construct(
        private QdrantService $qdrantService
    ) {}

    /**
     * Delete footage with all associated data (storage file, Qdrant point)
     */
    public function execute(Footage $footage, bool $forceDelete = false): bool
    {
        return DB::transaction(function () use ($footage, $forceDelete) {
            try {
                $footageId = $footage->id;
                
                // 1. Delete Qdrant embedding point
                $this->deleteQdrantPoint($footageId);
                
                // 2. Delete storage file
                $this->deleteStorageFile($footage);
                
                // 3. Delete database record
                if ($forceDelete) {
                    $footage->forceDelete();
                } else {
                    $footage->delete();
                }
                
                Log::info('Footage deleted successfully', ['footage_id' => $footageId]);
                
                return true;
            } catch (\Exception $e) {
                Log::error('Failed to delete footage', [
                    'footage_id' => $footage->id,
                    'error' => $e->getMessage(),
                ]);
                throw $e;
            }
        });
    }

    /**
     * Delete Qdrant embedding point
     */
    private function deleteQdrantPoint(int $footageId): void
    {
        try {
            // Point ID is the footage ID (numeric)
            $deleted = $this->qdrantService->deletePoint((string) $footageId);
            
            if ($deleted) {
                Log::debug('Qdrant point deleted', ['footage_id' => $footageId]);
            } else {
                Log::warning('Qdrant point not found or already deleted', ['footage_id' => $footageId]);
            }
        } catch (\Exception $e) {
            // Log but don't fail - Qdrant point might not exist
            Log::warning('Failed to delete Qdrant point', [
                'footage_id' => $footageId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Delete storage file
     */
    private function deleteStorageFile(Footage $footage): void
    {
        $storageFile = $footage->storageFile;
        
        if (!$storageFile) {
            Log::debug('No storage file associated with footage', ['footage_id' => $footage->id]);
            return;
        }

        try {
            // Get the storage driver using static factory method
            $driver = StorageDriverFactory::make($storageFile->driver);
            
            // Delete the physical file
            if ($driver->exists($storageFile->path)) {
                $driver->delete($storageFile->path);
                Log::debug('Storage file deleted', [
                    'footage_id' => $footage->id,
                    'path' => $storageFile->path,
                ]);
            }
            
            // Delete the storage file record
            $storageFile->forceDelete();
            
        } catch (\Exception $e) {
            Log::error('Failed to delete storage file', [
                'footage_id' => $footage->id,
                'storage_file_id' => $storageFile->id,
                'path' => $storageFile->path,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
