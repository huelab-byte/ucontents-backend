<?php

declare(strict_types=1);

namespace Modules\FootageLibrary\Actions;

use Modules\FootageLibrary\DTOs\UpdateFootageDTO;
use Modules\FootageLibrary\Models\Footage;
use Illuminate\Support\Facades\Log;

class UpdateFootageAction
{
    public function __construct(
        private UpdateFootageMetadataAction $updateMetadataAction
    ) {}

    /**
     * Update footage with title, folder, and/or metadata
     */
    public function execute(Footage $footage, UpdateFootageDTO $dto): Footage
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
                $footage->update($updates);
            }

            if ($dto->metadata !== null) {
                $this->updateMetadataAction->execute($footage, $dto->metadata);
            }

            return $footage->fresh(['storageFile', 'folder']);
        } catch (\Exception $e) {
            Log::error('Failed to update footage', [
                'footage_id' => $footage->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
