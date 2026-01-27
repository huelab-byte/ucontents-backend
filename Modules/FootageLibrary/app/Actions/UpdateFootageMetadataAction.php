<?php

declare(strict_types=1);

namespace Modules\FootageLibrary\Actions;

use Modules\FootageLibrary\Models\Footage;
use Illuminate\Support\Facades\Log;

class UpdateFootageMetadataAction
{
    /**
     * Update footage metadata
     */
    public function execute(Footage $footage, array $metadata): Footage
    {
        try {
            $existingMetadata = $footage->metadata;
            $updatedMetadata = array_merge($existingMetadata, $metadata);
            
            $footage->update(['metadata' => $updatedMetadata]);
            
            return $footage->fresh();
        } catch (\Exception $e) {
            Log::error('Failed to update footage metadata', [
                'footage_id' => $footage->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
