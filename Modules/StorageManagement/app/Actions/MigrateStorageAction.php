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

        // Get all files from database
        $files = StorageFile::where('driver', $sourceSetting->driver)->get();
        
        $migrated = 0;
        $failed = 0;
        $errors = [];

        DB::beginTransaction();
        try {
            foreach ($files as $file) {
                try {
                    // Check if file exists in source
                    if (!$sourceDriver->exists($file->path)) {
                        Log::warning("File not found in source storage: {$file->path}");
                        $failed++;
                        continue;
                    }

                    // Copy file to destination
                    $destinationPath = $file->path; // Keep same path structure
                    if (!$destinationDriver->copy($file->path, $destinationPath)) {
                        // If copy doesn't work, try upload
                        $sourceContent = $sourceDriver->exists($file->path) 
                            ? file_get_contents($sourceDriver->url($file->path) ?? '')
                            : null;
                        
                        if ($sourceContent) {
                            $tempFile = tempnam(sys_get_temp_dir(), 'migrate_');
                            file_put_contents($tempFile, $sourceContent);
                            $destinationDriver->upload($tempFile, $destinationPath);
                            unlink($tempFile);
                        } else {
                            throw new \Exception("Could not read source file");
                        }
                    }

                    // Update file record
                    $file->update([
                        'driver' => $destinationSetting->driver,
                        'url' => $destinationDriver->url($destinationPath),
                    ]);

                    $migrated++;
                } catch (\Exception $e) {
                    $failed++;
                    $errors[] = [
                        'file_id' => $file->id,
                        'path' => $file->path,
                        'error' => $e->getMessage(),
                    ];
                    Log::error("Failed to migrate file: {$file->path}", [
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            DB::commit();

            return [
                'migrated' => $migrated,
                'failed' => $failed,
                'total' => $files->count(),
                'errors' => $errors,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
