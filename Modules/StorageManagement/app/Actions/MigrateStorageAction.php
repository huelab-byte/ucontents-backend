<?php

declare(strict_types=1);

namespace Modules\StorageManagement\Actions;

use Modules\StorageManagement\Factories\StorageDriverFactory;
use Modules\StorageManagement\Models\StorageSetting;
use Modules\StorageManagement\Models\StorageFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MigrateStorageAction
{
    public function execute(StorageSetting $sourceSetting, StorageSetting $destinationSetting): array
    {
        $sourceDriver = StorageDriverFactory::make($sourceSetting->driver, $sourceSetting->toArray());
        $destinationDriver = StorageDriverFactory::make($destinationSetting->driver, $destinationSetting->toArray());

        // Get all files from database that belong to the source storage
        $files = StorageFile::where('driver', $sourceSetting->driver)->get();
        
        $migrated = 0;
        $failed = 0;
        $skipped = 0;
        $errors = [];

        foreach ($files as $file) {
            $localPath = null;
            
            try {
                // Check if file exists in source
                if (!$sourceDriver->exists($file->path)) {
                    Log::warning("File not found in source storage, skipping", [
                        'file_id' => $file->id,
                        'path' => $file->path,
                    ]);
                    $skipped++;
                    continue;
                }

                // Download file from source to local temp
                $localPath = $sourceDriver->getLocalPath($file->path);
                
                if (!file_exists($localPath)) {
                    throw new \Exception("Failed to download file from source storage");
                }

                // Upload to destination using uploadFile method (preserves exact path)
                $destinationPath = $file->path; // Keep same path structure
                $this->uploadToDestination($destinationDriver, $localPath, $destinationPath, $file->mime_type);

                // Update file record in database
                DB::beginTransaction();
                try {
                    $file->update([
                        'driver' => $destinationSetting->driver,
                        'url' => $destinationDriver->url($destinationPath),
                    ]);
                    DB::commit();
                } catch (\Exception $e) {
                    DB::rollBack();
                    throw $e;
                }

                // Optionally delete from source after successful migration
                // Uncomment the following line to delete source files after migration:
                // $sourceDriver->delete($file->path);

                $migrated++;
                
                Log::info("File migrated successfully", [
                    'file_id' => $file->id,
                    'path' => $file->path,
                    'from' => $sourceSetting->driver,
                    'to' => $destinationSetting->driver,
                ]);

            } catch (\Exception $e) {
                $failed++;
                $errors[] = [
                    'file_id' => $file->id,
                    'path' => $file->path,
                    'error' => $e->getMessage(),
                ];
                Log::error("Failed to migrate file", [
                    'file_id' => $file->id,
                    'path' => $file->path,
                    'error' => $e->getMessage(),
                ]);
            } finally {
                // Clean up temp file
                if ($localPath) {
                    $sourceDriver->cleanupLocalPath($localPath, $file->path);
                }
            }
        }

        return [
            'migrated' => $migrated,
            'failed' => $failed,
            'skipped' => $skipped,
            'total' => $files->count(),
            'errors' => $errors,
        ];
    }

    /**
     * Upload file to destination storage preserving exact path
     */
    private function uploadToDestination($driver, string $localFilePath, string $destinationPath, ?string $mimeType = null): void
    {
        $content = file_get_contents($localFilePath);
        if ($content === false) {
            throw new \Exception("Could not read local file: {$localFilePath}");
        }

        // Use putObjectDirect which all drivers implement
        // This preserves the exact path without generating a new filename
        $driver->putObjectDirect($destinationPath, $content, $mimeType);
    }
}
