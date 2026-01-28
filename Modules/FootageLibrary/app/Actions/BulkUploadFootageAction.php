<?php

declare(strict_types=1);

namespace Modules\FootageLibrary\Actions;

use Illuminate\Http\UploadedFile;
use Modules\FootageLibrary\Models\FootageUploadQueue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Log;

class BulkUploadFootageAction
{
    /**
     * Queue multiple footage files for upload
     */
    public function execute(
        array $files,
        ?int $folderId = null,
        string $metadataSource = 'title',
        ?int $userId = null
    ): array {
        $userId = $userId ?? auth()->id();
        $queuedItems = [];

        foreach ($files as $file) {
            try {
                // Store file temporarily in local disk (not the active storage provider)
                // Temp files are processed locally before being uploaded to final destination
                $tempPath = $file->store('temp/footage-uploads', 'local');
                
                // Create queue entry
                $queueItem = FootageUploadQueue::create([
                    'user_id' => $userId,
                    'file_name' => $file->getClientOriginalName(),
                    'file_path' => $tempPath,
                    'file_size' => $file->getSize(),
                    'mime_type' => $file->getMimeType(),
                    'folder_id' => $folderId,
                    'metadata_source' => $metadataSource,
                    'status' => 'pending',
                ]);

                // Dispatch job to process upload
                Queue::push(\Modules\FootageLibrary\Jobs\ProcessFootageUploadJob::class, [
                    'queue_id' => $queueItem->id,
                ]);

                $queuedItems[] = $queueItem;
            } catch (\Exception $e) {
                Log::error('Failed to queue footage upload', [
                    'file' => $file->getClientOriginalName(),
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $queuedItems;
    }
}
