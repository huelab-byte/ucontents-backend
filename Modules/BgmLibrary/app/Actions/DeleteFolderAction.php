<?php

declare(strict_types=1);

namespace Modules\BgmLibrary\Actions;

use Modules\BgmLibrary\Models\BgmFolder;
use Modules\BgmLibrary\Models\Bgm;
use Modules\StorageManagement\Factories\StorageDriverFactory;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class DeleteFolderAction
{
    public function __construct(
        private DeleteBgmAction $deleteBgmAction
    ) {}

    /**
     * Delete folder with all contents (BGM, child folders, storage files)
     */
    public function execute(BgmFolder $folder, bool $forceDelete = false): array
    {
        // Store folder path before deletion for storage cleanup
        $folderPath = $folder->path;
        
        return DB::transaction(function () use ($folder, $folderPath, $forceDelete) {
            $stats = [
                'folders_deleted' => 0,
                'bgm_deleted' => 0,
                'storage_dirs_deleted' => 0,
                'errors' => [],
            ];

            try {
                // 1. Recursively delete child folders first (including their storage directories)
                $this->deleteChildFolders($folder, $forceDelete, $stats);

                // 2. Delete all BGM in this folder
                $this->deleteFolderBgm($folder, $forceDelete, $stats);

                // 3. Delete the storage directory for this folder
                $this->deleteStorageDirectory($folderPath, $stats);

                // 4. Delete the folder itself from database
                if ($forceDelete) {
                    $folder->forceDelete();
                } else {
                    $folder->delete();
                }
                $stats['folders_deleted']++;

                Log::info('Folder deleted successfully', [
                    'folder_id' => $folder->id,
                    'folder_name' => $folder->name,
                    'stats' => $stats,
                ]);

                return $stats;
            } catch (\Exception $e) {
                Log::error('Failed to delete folder', [
                    'folder_id' => $folder->id,
                    'error' => $e->getMessage(),
                ]);
                throw $e;
            }
        });
    }

    /**
     * Recursively delete child folders
     */
    private function deleteChildFolders(BgmFolder $folder, bool $forceDelete, array &$stats): void
    {
        $children = $folder->children()->get();

        foreach ($children as $childFolder) {
            // Store path before deletion
            $childPath = $childFolder->path;
            
            // Recursively delete child's children first
            $this->deleteChildFolders($childFolder, $forceDelete, $stats);

            // Delete BGM in child folder
            $this->deleteFolderBgm($childFolder, $forceDelete, $stats);

            // Delete the storage directory for child folder
            $this->deleteStorageDirectory($childPath, $stats);

            // Delete the child folder from database
            try {
                if ($forceDelete) {
                    $childFolder->forceDelete();
                } else {
                    $childFolder->delete();
                }
                $stats['folders_deleted']++;
                
                Log::debug('Child folder deleted', [
                    'folder_id' => $childFolder->id,
                    'folder_name' => $childFolder->name,
                ]);
            } catch (\Exception $e) {
                $stats['errors'][] = [
                    'type' => 'folder',
                    'id' => $childFolder->id,
                    'error' => $e->getMessage(),
                ];
                Log::error('Failed to delete child folder', [
                    'folder_id' => $childFolder->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Delete all BGM in a folder
     */
    private function deleteFolderBgm(BgmFolder $folder, bool $forceDelete, array &$stats): void
    {
        $bgmItems = $folder->bgm()->get();

        foreach ($bgmItems as $bgm) {
            try {
                $this->deleteBgmAction->execute($bgm, $forceDelete);
                $stats['bgm_deleted']++;
            } catch (\Exception $e) {
                $stats['errors'][] = [
                    'type' => 'bgm',
                    'id' => $bgm->id,
                    'error' => $e->getMessage(),
                ];
                Log::error('Failed to delete BGM in folder', [
                    'folder_id' => $folder->id,
                    'bgm_id' => $bgm->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Delete storage directory for a folder
     */
    private function deleteStorageDirectory(?string $folderPath, array &$stats): void
    {
        if (!$folderPath) {
            return;
        }

        try {
            $storagePath = 'bgm-library/' . $folderPath;
            
            // Use StorageManagement driver to delete directory
            $driver = StorageDriverFactory::make();
            
            if ($driver->deleteDirectory($storagePath)) {
                // Verify deletion was successful by checking if directory still exists
                // This ensures empty directories are properly removed
                $files = $driver->listFiles($storagePath, false);
                if (empty($files)) {
                    $stats['storage_dirs_deleted']++;
                    Log::debug('Storage directory deleted and verified via StorageManagement', ['path' => $storagePath]);
                } else {
                    // Directory still has files, deletion was not fully successful
                    $stats['errors'][] = [
                        'type' => 'storage_directory',
                        'path' => $folderPath,
                        'error' => 'Directory deletion incomplete - files still remain',
                    ];
                    Log::warning('Storage directory deletion incomplete', [
                        'path' => $storagePath,
                        'remaining_files' => count($files),
                    ]);
                }
            } else {
                // deleteDirectory returned false
                $stats['errors'][] = [
                    'type' => 'storage_directory',
                    'path' => $folderPath,
                    'error' => 'deleteDirectory returned false',
                ];
                Log::warning('Storage directory deletion failed', ['path' => $storagePath]);
            }
        } catch (\Exception $e) {
            // Log but don't fail - directory might not exist or be empty already
            $stats['errors'][] = [
                'type' => 'storage_directory',
                'path' => $folderPath,
                'error' => $e->getMessage(),
            ];
            Log::warning('Failed to delete storage directory', [
                'path' => $folderPath,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
