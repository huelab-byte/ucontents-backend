<?php

declare(strict_types=1);

namespace Modules\MediaUpload\Actions;

use Modules\MediaUpload\Models\MediaUploadFolder;
use Modules\StorageManagement\Factories\StorageDriverFactory;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class DeleteFolderAction
{
    public function __construct(
        private DeleteMediaUploadAction $deleteMediaUploadAction
    ) {}

    public function execute(MediaUploadFolder $folder): void
    {
        // Capture folder info BEFORE transaction deletes the record
        $folderId = $folder->id;
        $storagePath = $folder->storage_path;

        DB::transaction(function () use ($folder) {
            // Recursively delete child folders first
            foreach ($folder->children as $child) {
                $this->execute($child);
            }

            // Delete all media uploads and their storage files
            foreach ($folder->mediaUploads as $upload) {
                $this->deleteMediaUploadAction->execute($upload);
            }

            // Delete queue items and their temp files
            foreach ($folder->queueItems as $item) {
                if ($item->file_path && Storage::disk('local')->exists($item->file_path)) {
                    Storage::disk('local')->delete($item->file_path);
                }
            }

            $folder->queueItems()->delete();
            $folder->contentSettings()->delete();
            $folder->delete();
        });

        // Delete storage directories AFTER transaction succeeds
        // This ensures we don't delete files if DB deletion fails
        $this->deleteStorageDirectories($folderId, $storagePath);
    }

    /**
     * Delete the folder's directory from storage using StorageManagement module
     * Handles both old (folder-{id}) and new (storage_path) naming conventions
     */
    private function deleteStorageDirectories(int $folderId, ?string $storagePath): void
    {
        try {
            $driver = StorageDriverFactory::make();
            $pathsToDelete = [];

            // Always try to delete the legacy path (folder-{id})
            // This handles folders created before the storage_path migration
            $legacyPath = 'media-upload/folder-' . $folderId;
            $pathsToDelete[] = $legacyPath;

            // Also delete the new storage_path if it exists and is different
            if ($storagePath) {
                $newPath = 'media-upload/' . $storagePath;
                if ($newPath !== $legacyPath) {
                    $pathsToDelete[] = $newPath;
                }
            }

            foreach ($pathsToDelete as $path) {
                try {
                    $deleted = $driver->deleteDirectory($path);
                    if ($deleted) {
                        Log::info('Deleted storage directory for folder', [
                            'folder_id' => $folderId,
                            'storage_path' => $path,
                        ]);
                    }
                } catch (\Exception $e) {
                    Log::warning('Failed to delete storage directory', [
                        'folder_id' => $folderId,
                        'storage_path' => $path,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        } catch (\Exception $e) {
            // Log but don't throw - folder deletion in DB already succeeded
            Log::error('Error deleting storage directories for folder', [
                'folder_id' => $folderId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
