<?php

declare(strict_types=1);

namespace Modules\BgmLibrary\Actions;

use Illuminate\Http\UploadedFile;
use Modules\BgmLibrary\Models\BgmUploadQueue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Log;

class BulkUploadBgmAction
{
    /**
     * Queue multiple BGM files for upload
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
                // Store file temporarily in local disk (not the active storage provider)
                // Temp files are processed locally before being uploaded to final destination
                $tempPath = $file->store('temp/bgm-uploads', 'local');
                
                // Create queue entry
                $queueItem = BgmUploadQueue::create([
                    'user_id' => $userId,
                    'file_name' => $file->getClientOriginalName(),
                    'file_path' => $tempPath,
                    'file_size' => $file->getSize(),
                    'mime_type' => $file->getMimeType(),
                    'folder_id' => $folderId,
                    'status' => 'pending',
                ]);

                // Dispatch job to process upload
                Queue::push(\Modules\BgmLibrary\Jobs\ProcessBgmUploadJob::class, [
                    'queue_id' => $queueItem->id,
                ]);

                $queuedItems[] = $queueItem;
            } catch (\Exception $e) {
                Log::error('Failed to queue BGM upload', [
                    'file' => $file->getClientOriginalName(),
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $queuedItems;
    }
}
