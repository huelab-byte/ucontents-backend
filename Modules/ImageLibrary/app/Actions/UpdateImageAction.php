<?php

declare(strict_types=1);

namespace Modules\ImageLibrary\Actions;

use Modules\ImageLibrary\DTOs\UpdateImageDTO;
use Modules\ImageLibrary\Models\Image;
use Illuminate\Support\Facades\Log;

class UpdateImageAction
{
    /**
     * Update image with title, folder, and/or metadata
     */
    public function execute(Image $image, UpdateImageDTO $dto): Image
    {
        try {
            $updates = [];

            if ($dto->title !== null) {
                $updates['title'] = $dto->title;
            }

            if ($dto->folderId !== null) {
                $updates['folder_id'] = $dto->folderId;
            }

            // Handle metadata updates by merging with existing
            if ($dto->metadata !== null) {
                $existingMetadata = $image->metadata ?? [];
                $updates['metadata'] = array_merge($existingMetadata, $dto->metadata);
            }

            if (!empty($updates)) {
                $image->update($updates);
            }

            return $image->fresh(['storageFile', 'folder']);
        } catch (\Exception $e) {
            Log::error('Failed to update image', [
                'image_id' => $image->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
