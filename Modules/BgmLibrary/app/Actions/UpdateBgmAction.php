<?php

declare(strict_types=1);

namespace Modules\BgmLibrary\Actions;

use Modules\BgmLibrary\DTOs\UpdateBgmDTO;
use Modules\BgmLibrary\Models\Bgm;
use Illuminate\Support\Facades\Log;

class UpdateBgmAction
{
    /**
     * Update BGM with title, folder, and/or metadata
     */
    public function execute(Bgm $bgm, UpdateBgmDTO $dto): Bgm
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
                $existingMetadata = $bgm->metadata ?? [];
                $updates['metadata'] = array_merge($existingMetadata, $dto->metadata);
            }

            if (!empty($updates)) {
                $bgm->update($updates);
            }

            return $bgm->fresh(['storageFile', 'folder']);
        } catch (\Exception $e) {
            Log::error('Failed to update BGM', [
                'bgm_id' => $bgm->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
