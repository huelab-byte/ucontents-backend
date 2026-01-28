<?php

declare(strict_types=1);

namespace Modules\VideoOverlay\Actions;

use Modules\VideoOverlay\DTOs\UpdateVideoOverlayDTO;
use Modules\VideoOverlay\Models\VideoOverlay;
use Illuminate\Support\Facades\Log;

class UpdateVideoOverlayAction
{
    /**
     * Update video overlay with title and/or folder
     */
    public function execute(VideoOverlay $videoOverlay, UpdateVideoOverlayDTO $dto): VideoOverlay
    {
        try {
            $updates = [];

            if ($dto->title !== null) {
                $updates['title'] = $dto->title;
            }

            if ($dto->folderId !== null) {
                $updates['folder_id'] = $dto->folderId;
            }

            if (!empty($updates)) {
                $videoOverlay->update($updates);
            }

            return $videoOverlay->fresh(['storageFile', 'folder']);
        } catch (\Exception $e) {
            Log::error('Failed to update video overlay', [
                'video_overlay_id' => $videoOverlay->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
