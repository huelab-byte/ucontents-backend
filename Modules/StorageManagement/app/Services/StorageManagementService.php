<?php

declare(strict_types=1);

namespace Modules\StorageManagement\Services;

use Modules\StorageManagement\Models\StorageSetting;
use Modules\StorageManagement\Models\StorageFile;
use Modules\StorageManagement\Factories\StorageDriverFactory;
use Modules\StorageManagement\Actions\CreateStorageConfigAction;
use Modules\StorageManagement\Actions\UpdateStorageConfigAction;
use Modules\StorageManagement\Actions\MigrateStorageAction;
use Modules\StorageManagement\DTOs\StorageConfigDTO;
use Illuminate\Support\Facades\DB;

class StorageManagementService
{
    public function __construct(
        private CreateStorageConfigAction $createConfigAction,
        private UpdateStorageConfigAction $updateConfigAction,
        private MigrateStorageAction $migrateAction,
    ) {}

    /**
     * Get current storage configuration
     */
    public function getCurrentConfig(): ?StorageSetting
    {
        return StorageSetting::getActive();
    }

    /**
     * Create or update storage configuration
     */
    public function saveConfig(StorageConfigDTO $dto, ?int $id = null): StorageSetting
    {
        if ($id) {
            $setting = StorageSetting::findOrFail($id);
            return $this->updateConfigAction->execute($setting, $dto);
        }

        return $this->createConfigAction->execute($dto);
    }

    /**
     * Test storage connection
     */
    public function testConnection(StorageConfigDTO $dto): bool
    {
        try {
            $driver = StorageDriverFactory::make($dto->driver, [
                'key' => $dto->key,
                'secret' => $dto->secret,
                'region' => $dto->region,
                'bucket' => $dto->bucket,
                'endpoint' => $dto->endpoint,
                'use_path_style_endpoint' => $dto->usePathStyleEndpoint,
                'disk' => $dto->rootPath ? 'local' : 'local',
            ]);

            return $driver->testConnection();
        } catch (\Exception $e) {
            \Log::error('Storage connection test failed: ' . $e->getMessage());
            throw new \RuntimeException('Connection test failed: ' . $e->getMessage());
        }
    }

    /**
     * Get storage usage statistics
     */
    public function getUsage(): array
    {
        $activeSetting = StorageSetting::getActive();
        if (!$activeSetting) {
            return [
                'total_size' => 0,
                'file_count' => 0,
                'driver' => null,
            ];
        }

        try {
            // Try to get usage from driver, but don't let it hang
            $usage = null;
            try {
                $driver = StorageDriverFactory::make();
                // Set a timeout for getUsage if it's slow (for S3 connections)
                $usage = $driver->getUsage();
            } catch (\Exception $driverException) {
                // If driver fails, we'll fall back to database only
                \Log::warning('Storage driver getUsage failed: ' . $driverException->getMessage());
            }

            // Always get count from database (fast and reliable)
            $fileCount = StorageFile::where('driver', $activeSetting->driver)->count();
            $totalSize = StorageFile::where('driver', $activeSetting->driver)->sum('size');

            return [
                'total_size' => $usage['total_size'] ?? $totalSize ?? 0,
                'file_count' => $usage['file_count'] ?? $fileCount ?? 0,
                'driver' => $activeSetting->driver,
                'database_file_count' => $fileCount,
                'database_total_size' => $totalSize,
            ];
        } catch (\Exception $e) {
            // Final fallback - just return database stats
            try {
                $fileCount = StorageFile::where('driver', $activeSetting->driver)->count();
                $totalSize = StorageFile::where('driver', $activeSetting->driver)->sum('size');
                
                return [
                    'total_size' => $totalSize ?? 0,
                    'file_count' => $fileCount ?? 0,
                    'driver' => $activeSetting->driver,
                    'database_file_count' => $fileCount,
                    'database_total_size' => $totalSize,
                    'error' => $e->getMessage(),
                ];
            } catch (\Exception $dbException) {
                // Even database query failed, return empty
                return [
                    'total_size' => 0,
                    'file_count' => 0,
                    'driver' => $activeSetting->driver,
                    'error' => $dbException->getMessage(),
                ];
            }
        }
    }

    /**
     * Migrate storage from one to another
     */
    public function migrateStorage(int $sourceId, int $destinationId): array
    {
        $sourceSetting = StorageSetting::findOrFail($sourceId);
        $destinationSetting = StorageSetting::findOrFail($destinationId);

        return $this->migrateAction->execute($sourceSetting, $destinationSetting);
    }

    /**
     * Clean unused files
     */
    public function cleanUnusedFiles(int $olderThanDays = 30): array
    {
        $cutoffDate = now()->subDays($olderThanDays);
        
        $unusedFiles = StorageFile::where('is_used', false)
            ->where(function ($query) use ($cutoffDate) {
                $query->whereNull('last_accessed_at')
                    ->orWhere('last_accessed_at', '<', $cutoffDate);
            })
            ->get();

        $deleted = 0;
        $failed = 0;
        $errors = [];

        try {
            $driver = StorageDriverFactory::make();
            
            foreach ($unusedFiles as $file) {
                try {
                    if ($driver->exists($file->path)) {
                        $driver->delete($file->path);
                    }
                    $file->delete();
                    $deleted++;
                } catch (\Exception $e) {
                    $failed++;
                    $errors[] = [
                        'file_id' => $file->id,
                        'path' => $file->path,
                        'error' => $e->getMessage(),
                    ];
                }
            }

            return [
                'deleted' => $deleted,
                'failed' => $failed,
                'total' => $unusedFiles->count(),
                'errors' => $errors,
            ];
        } catch (\Exception $e) {
            throw new \RuntimeException('Failed to clean unused files: ' . $e->getMessage());
        }
    }
}
