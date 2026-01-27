<?php

declare(strict_types=1);

namespace Modules\AudioLibrary\Actions;

use Modules\AudioLibrary\Models\AudioFolder;
use Illuminate\Support\Facades\Log;

class CreateFolderAction
{
    /**
     * Create a new folder
     */
    public function execute(string $name, ?int $parentId = null, ?int $userId = null): AudioFolder
    {
        try {
            $userId = $userId ?? auth()->id();

            // Check if folder with same name exists in parent
            $existing = AudioFolder::where('user_id', $userId)
                ->where('parent_id', $parentId)
                ->where('name', $name)
                ->first();

            if ($existing) {
                throw new \Exception("Folder with name '{$name}' already exists in this location");
            }

            // Create folder
            $folder = AudioFolder::create([
                'name' => $name,
                'parent_id' => $parentId,
                'user_id' => $userId,
            ]);

            // Path is calculated automatically in model boot method
            return $folder->fresh();
        } catch (\Exception $e) {
            Log::error('Failed to create folder', [
                'name' => $name,
                'parent_id' => $parentId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
