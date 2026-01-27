<?php

declare(strict_types=1);

namespace Modules\ImageLibrary\Actions;

use Modules\ImageLibrary\Models\ImageFolder;
use Modules\ImageLibrary\Models\Image;
use Modules\StorageManagement\Factories\StorageDriverFactory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class DeleteFolderAction
{
    /**
     * Delete a folder and all its contents
     */
    public function execute(ImageFolder $folder): array
    {
        $stats = [
            'deleted_folders' => 0,
            'deleted_images' => 0,
        ];

        DB::transaction(function () use ($folder, &$stats) {
            $this->deleteRecursive($folder, $stats);
        });

        return $stats;
    }

    /**
     * Recursively delete folder and contents
     */
    private function deleteRecursive(ImageFolder $folder, array &$stats): void
    {
        // Delete child folders first
        $children = ImageFolder::where('parent_id', $folder->id)->get();
        foreach ($children as $child) {
            $this->deleteRecursive($child, $stats);
        }

        // Delete images in this folder
        $images = Image::where('folder_id', $folder->id)->get();
        foreach ($images as $image) {
            $this->deleteImageStorageFile($image);
            $image->forceDelete();
            $stats['deleted_images']++;
        }

        // Delete storage directory for this folder
        $storagePath = 'image-library/' . $folder->path;
        if (Storage::exists($storagePath)) {
            Storage::deleteDirectory($storagePath);
        }

        // Delete the folder
        $folder->forceDelete();
        $stats['deleted_folders']++;
    }

    /**
     * Delete storage file associated with an image
     */
    private function deleteImageStorageFile(Image $image): void
    {
        $storageFile = $image->storageFile;
        
        if (!$storageFile) {
            return;
        }

        try {
            // Get the storage driver using static factory method
            $driver = StorageDriverFactory::make($storageFile->driver);
            
            // Delete the physical file
            if ($driver->exists($storageFile->path)) {
                $driver->delete($storageFile->path);
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
            // Continue with deletion even if file deletion fails
        }
    }
}
