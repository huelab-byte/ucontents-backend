<?php

declare(strict_types=1);

namespace Modules\MediaUpload\Actions;

use Modules\MediaUpload\DTOs\UpdateFolderDTO;
use Modules\MediaUpload\Models\MediaUploadFolder;
use Illuminate\Support\Facades\Log;

class UpdateFolderAction
{
    public function execute(MediaUploadFolder $folder, UpdateFolderDTO $dto): MediaUploadFolder
    {
        $updates = [];

        if ($dto->name !== null) {
            $existing = MediaUploadFolder::where('user_id', $folder->user_id)
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
            if ($dto->parentId === $folder->id) {
                throw new \Exception('A folder cannot be its own parent');
            }
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
    }

    private function getDescendantIds(MediaUploadFolder $folder): array
    {
        $ids = [];
        $children = MediaUploadFolder::where('parent_id', $folder->id)->get();
        foreach ($children as $child) {
            $ids[] = $child->id;
            $ids = array_merge($ids, $this->getDescendantIds($child));
        }
        return $ids;
    }
}
