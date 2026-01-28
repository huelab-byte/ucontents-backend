<?php

declare(strict_types=1);

namespace Modules\ImageOverlay\Actions;

use Illuminate\Http\UploadedFile;
use Modules\ImageOverlay\Models\ImageOverlayUploadQueue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Log;

class BulkUploadImageOverlayAction
{
    /**
     * Queue multiple image overlay files for upload
     */
    public function execute(
        array $files,
        ?int $folderId = null,
        ?int $userId = null
    ): array {
        $userId = $userId ?? auth()->id();
        $queuedItems = [];

        foreach ($files as $file) {
            try {
                // Store file temporarily in local disk
                $tempPath = $file->store('temp/image-overlay-uploads', 'local');
                
                // Create queue entry
                $queueItem = ImageOverlayUploadQueue::create([
                    'user_id' => $userId,
                    'file_name' => $file->getClientOriginalName(),
                    'file_path' => $tempPath,
                    'file_size' => $file->getSize(),
                    'mime_type' => $file->getMimeType(),
                    'folder_id' => $folderId,
                    'status' => 'pending',
                ]);

                // Dispatch job to process upload
                Queue::push(\Modules\ImageOverlay\Jobs\ProcessImageOverlayUploadJob::class, [
                    'queue_id' => $queueItem->id,
                ]);

                $queuedItems[] = $queueItem;
            } catch (\Exception $e) {
                Log::error('Failed to queue image overlay upload', [
                    'file' => $file->getClientOriginalName(),
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $queuedItems;
    }
}
