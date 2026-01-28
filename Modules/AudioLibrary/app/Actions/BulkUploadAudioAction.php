<?php

declare(strict_types=1);

namespace Modules\AudioLibrary\Actions;

use Illuminate\Http\UploadedFile;
use Modules\AudioLibrary\Models\AudioUploadQueue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Log;

class BulkUploadAudioAction
{
    /**
     * Queue multiple audio files for upload
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
                $tempPath = $file->store('temp/audio-uploads', 'local');
                
                // Create queue entry
                $queueItem = AudioUploadQueue::create([
                    'user_id' => $userId,
                    'file_name' => $file->getClientOriginalName(),
                    'file_path' => $tempPath,
                    'file_size' => $file->getSize(),
                    'mime_type' => $file->getMimeType(),
                    'folder_id' => $folderId,
                    'status' => 'pending',
                ]);

                // Dispatch job to process upload
                Queue::push(\Modules\AudioLibrary\Jobs\ProcessAudioUploadJob::class, [
                    'queue_id' => $queueItem->id,
                ]);

                $queuedItems[] = $queueItem;
            } catch (\Exception $e) {
                Log::error('Failed to queue audio upload', [
                    'file' => $file->getClientOriginalName(),
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $queuedItems;
    }
}
