<?php

declare(strict_types=1);

namespace Modules\AudioLibrary\Actions;

use Modules\AudioLibrary\Models\AudioFolder;
use Modules\AudioLibrary\Models\Audio;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class DeleteFolderAction
{
    public function __construct(
        private DeleteAudioAction $deleteAudioAction
    ) {}

    /**
     * Delete folder with all contents (audio, child folders, storage files)
     */
    public function execute(AudioFolder $folder, bool $forceDelete = false): array
    {
        // Store folder path before deletion for storage cleanup
        $folderPath = $folder->path;
        
        return DB::transaction(function () use ($folder, $folderPath, $forceDelete) {
            $stats = [
                'folders_deleted' => 0,
                'audio_deleted' => 0,
                'storage_dirs_deleted' => 0,
                'errors' => [],
            ];

            try {
                // 1. Recursively delete child folders first (including their storage directories)
                $this->deleteChildFolders($folder, $forceDelete, $stats);

                // 2. Delete all audio in this folder
                $this->deleteFolderAudio($folder, $forceDelete, $stats);

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
    private function deleteChildFolders(AudioFolder $folder, bool $forceDelete, array &$stats): void
    {
        $children = $folder->children()->get();

        foreach ($children as $childFolder) {
            // Store path before deletion
            $childPath = $childFolder->path;
            
            // Recursively delete child's children first
            $this->deleteChildFolders($childFolder, $forceDelete, $stats);

            // Delete audio in child folder
            $this->deleteFolderAudio($childFolder, $forceDelete, $stats);

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
     * Delete all audio in a folder
     */
    private function deleteFolderAudio(AudioFolder $folder, bool $forceDelete, array &$stats): void
    {
        $audioItems = $folder->audio()->get();

        foreach ($audioItems as $audio) {
            try {
                $this->deleteAudioAction->execute($audio, $forceDelete);
                $stats['audio_deleted']++;
            } catch (\Exception $e) {
                $stats['errors'][] = [
                    'type' => 'audio',
                    'id' => $audio->id,
                    'error' => $e->getMessage(),
                ];
                Log::error('Failed to delete audio in folder', [
                    'folder_id' => $folder->id,
                    'audio_id' => $audio->id,
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
            $storagePath = 'audio-library/' . $folderPath;
            
            // Use Laravel's Storage facade to delete directory
            // This works for both local and S3 storage
            if (Storage::disk('public')->exists($storagePath)) {
                Storage::disk('public')->deleteDirectory($storagePath);
                $stats['storage_dirs_deleted']++;
                
                Log::debug('Storage directory deleted', ['path' => $storagePath]);
            } else {
                // Try direct filesystem path for local storage
                $fullPath = storage_path('app/public/' . $storagePath);
                if (File::isDirectory($fullPath)) {
                    File::deleteDirectory($fullPath);
                    $stats['storage_dirs_deleted']++;
                    
                    Log::debug('Storage directory deleted (direct)', ['path' => $fullPath]);
                }
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
