<?php

declare(strict_types=1);

namespace Modules\ImageLibrary\Actions;

use Modules\ImageLibrary\Models\ImageFolder;
use Modules\ImageLibrary\DTOs\UpdateFolderDTO;
use Illuminate\Support\Facades\Log;

class UpdateFolderAction
{
    /**
     * Update a folder
     */
    public function execute(ImageFolder $folder, UpdateFolderDTO $dto): ImageFolder
    {
        $updates = [];

        if ($dto->name !== null) {
            // Check for duplicate name in the same parent
            $parentId = $dto->parentId ?? $folder->parent_id;
            $exists = ImageFolder::where('user_id', $folder->user_id)
                ->where('name', $dto->name)
                ->where('parent_id', $parentId)
                ->where('id', '!=', $folder->id)
                ->exists();

            if ($exists) {
                throw new \Exception('A folder with this name already exists in this location');
            }

            $updates['name'] = $dto->name;
        }

        if ($dto->parentId !== null) {
            // Prevent circular references
            if ($dto->parentId === $folder->id) {
                throw new \Exception('Cannot set folder as its own parent');
            }

            // Check if the new parent is a descendant of this folder
            $descendantIds = $this->getDescendantIds($folder);
            if (in_array($dto->parentId, $descendantIds)) {
                throw new \Exception('Cannot move folder to its own descendant');
            }

            $updates['parent_id'] = $dto->parentId;
        }

        if (!empty($updates)) {
            $folder->update($updates);
        }

        return $folder->fresh();
    }

    /**
     * Get all descendant folder IDs
     */
    private function getDescendantIds(ImageFolder $folder): array
    {
        $ids = [];
        $children = ImageFolder::where('parent_id', $folder->id)->get();

        foreach ($children as $child) {
            $ids[] = $child->id;
            $ids = array_merge($ids, $this->getDescendantIds($child));
        }

        return $ids;
    }
}
