<?php

declare(strict_types=1);

namespace Modules\ImageOverlay\Actions;

use Modules\ImageOverlay\Models\ImageOverlayFolder;
use Illuminate\Support\Facades\Log;

class CreateFolderAction
{
    /**
     * Create a new folder
     */
    public function execute(string $name, ?int $parentId = null, ?int $userId = null): ImageOverlayFolder
    {
        $userId = $userId ?? auth()->id();

        // Check for duplicate name in the same parent
        $exists = ImageOverlayFolder::where('user_id', $userId)
            ->where('name', $name)
            ->where('parent_id', $parentId)
            ->exists();

        if ($exists) {
            throw new \Exception('A folder with this name already exists in this location');
        }

        return ImageOverlayFolder::create([
            'name' => $name,
            'parent_id' => $parentId,
            'user_id' => $userId,
        ]);
    }
}
