<?php

declare(strict_types=1);

namespace Modules\ImageOverlay\Actions;

use Modules\ImageOverlay\DTOs\UpdateImageOverlayDTO;
use Modules\ImageOverlay\Models\ImageOverlay;
use Illuminate\Support\Facades\Log;

class UpdateImageOverlayAction
{
    /**
     * Update image overlay with title, folder, and/or metadata
     */
    public function execute(ImageOverlay $imageOverlay, UpdateImageOverlayDTO $dto): ImageOverlay
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
                $existingMetadata = $imageOverlay->metadata ?? [];
                $updates['metadata'] = array_merge($existingMetadata, $dto->metadata);
            }

            if (!empty($updates)) {
                $imageOverlay->update($updates);
            }

            return $imageOverlay->fresh(['storageFile', 'folder']);
        } catch (\Exception $e) {
            Log::error('Failed to update image overlay', [
                'image_overlay_id' => $imageOverlay->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
