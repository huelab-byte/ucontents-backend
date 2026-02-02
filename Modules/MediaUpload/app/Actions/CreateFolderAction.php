<?php

declare(strict_types=1);

namespace Modules\MediaUpload\Actions;

use Modules\MediaUpload\Models\MediaUploadFolder;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CreateFolderAction
{
    public function execute(string $name, ?int $parentId = null, ?int $userId = null): MediaUploadFolder
    {
        $userId = $userId ?? auth()->id();

        $existing = MediaUploadFolder::where('user_id', $userId)
            ->where('parent_id', $parentId)
            ->where('name', $name)
            ->first();

        if ($existing) {
            throw new \Exception("Folder with name '{$name}' already exists in this location");
        }

        // Generate storage path from folder name
        $storagePath = $this->generateUniqueStoragePath($name, $userId, $parentId);

        $folder = MediaUploadFolder::create([
            'name' => $name,
            'storage_path' => $storagePath,
            'parent_id' => $parentId,
            'user_id' => $userId,
        ]);

        return $folder->fresh();
    }

    /**
     * Generate a unique storage-safe path from folder name
     */
    private function generateUniqueStoragePath(string $name, int $userId, ?int $parentId): string
    {
        $basePath = $this->sanitizeFolderName($name);

        // Build parent prefix if nested folder
        $parentPrefix = '';
        if ($parentId) {
            $parent = MediaUploadFolder::find($parentId);
            if ($parent) {
                $parentPrefix = $parent->storage_path . '/';
            }
        }

        // Check for uniqueness at the same parent level
        $storagePath = $basePath;
        $counter = 1;

        while (MediaUploadFolder::where('user_id', $userId)
            ->where('parent_id', $parentId)
            ->where('storage_path', $storagePath)
            ->exists()) {
            $storagePath = $basePath . '-' . $counter;
            $counter++;
        }

        return $storagePath;
    }

    /**
     * Sanitize folder name for storage use
     */
    private function sanitizeFolderName(string $name): string
    {
        // Convert to slug (lowercase, hyphens)
        $sanitized = Str::slug($name, '-');

        // Ensure it's not empty
        if (empty($sanitized)) {
            $sanitized = 'folder-' . time();
        }

        // Limit length to prevent path issues (50 chars max)
        // Use substr instead of Str::limit to avoid '...' suffix
        if (strlen($sanitized) > 50) {
            $sanitized = substr($sanitized, 0, 50);
        }

        return $sanitized;
    }
}
