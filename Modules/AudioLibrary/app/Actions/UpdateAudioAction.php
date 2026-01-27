<?php

declare(strict_types=1);

namespace Modules\AudioLibrary\Actions;

use Modules\AudioLibrary\DTOs\UpdateAudioDTO;
use Modules\AudioLibrary\Models\Audio;
use Illuminate\Support\Facades\Log;

class UpdateAudioAction
{
    /**
     * Update audio with title, folder, and/or metadata
     */
    public function execute(Audio $audio, UpdateAudioDTO $dto): Audio
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
                $existingMetadata = $audio->metadata ?? [];
                $updates['metadata'] = array_merge($existingMetadata, $dto->metadata);
            }

            if (!empty($updates)) {
                $audio->update($updates);
            }

            return $audio->fresh(['storageFile', 'folder']);
        } catch (\Exception $e) {
            Log::error('Failed to update audio', [
                'audio_id' => $audio->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
