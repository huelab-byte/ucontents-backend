<?php

declare(strict_types=1);

namespace Modules\BgmLibrary\Actions;

use Modules\BgmLibrary\DTOs\UpdateFolderDTO;
use Modules\BgmLibrary\Models\BgmFolder;
use Illuminate\Support\Facades\Log;

class UpdateFolderAction
{
    /**
     * Update a folder
     */
    public function execute(BgmFolder $folder, UpdateFolderDTO $dto): BgmFolder
    {
        try {
            $updates = [];

            if ($dto->name !== null) {
                // Check if folder with same name exists in parent
                $existing = BgmFolder::where('user_id', $folder->user_id)
                    ->where('parent_id', $dto->parentId ?? $folder->parent_id)
                    ->where('name', $dto->name)
                    ->where('id', '!=', $folder->id)
                    ->first();

                if ($existing) {
                    throw new \Exception("Folder with name '{$dto->name}' already exists in this location");
                }

                $updates['name'] = $dto->name;
            }

            if ($dto->parentId !== null) {
                // Validate that we're not creating a circular reference
                if ($dto->parentId === $folder->id) {
                    throw new \Exception('A folder cannot be its own parent');
                }

                // Check that new parent is not a descendant of this folder
                $descendantIds = $this->getDescendantIds($folder);
                if (in_array($dto->parentId, $descendantIds)) {
                    throw new \Exception('Cannot move a folder into one of its descendants');
                }

                $updates['parent_id'] = $dto->parentId;
            }

            if (!empty($updates)) {
                $folder->update($updates);
            }

            return $folder->fresh(['parent', 'children']);
        } catch (\Exception $e) {
            Log::error('Failed to update folder', [
                'folder_id' => $folder->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Get all descendant folder IDs
     */
    private function getDescendantIds(BgmFolder $folder): array
    {
        $ids = [];
        $children = BgmFolder::where('parent_id', $folder->id)->get();

        foreach ($children as $child) {
            $ids[] = $child->id;
            $ids = array_merge($ids, $this->getDescendantIds($child));
        }

        return $ids;
    }
}
