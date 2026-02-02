<?php

declare(strict_types=1);

namespace Modules\MediaUpload\Actions;

use Illuminate\Http\UploadedFile;
use Modules\MediaUpload\Models\MediaUploadQueue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class BulkUploadAction
{
    public function execute(array $files, int $folderId, ?int $userId = null, ?array $captionConfig = null): array
    {
        $userId = $userId ?? auth()->id();
        $tempPath = config('mediaupload.module.upload.temp_path', 'temp/media-uploads');
        $queuedItems = [];

        foreach ($files as $file) {
            try {
                $path = $file->store($tempPath, 'local');
                $queueItem = MediaUploadQueue::create([
                    'user_id' => $userId,
                    'folder_id' => $folderId,
                    'file_name' => $file->getClientOriginalName(),
                    'file_path' => $path,
                    'file_size' => $file->getSize(),
                    'mime_type' => $file->getMimeType(),
                    'caption_config' => $captionConfig,
                    'status' => 'pending',
                ]);

                \Modules\MediaUpload\Jobs\ProcessMediaUploadJob::dispatch($queueItem->id);

                $queuedItems[] = $queueItem;
            } catch (\Exception $e) {
                Log::error('Failed to queue media upload', [
                    'file' => $file->getClientOriginalName(),
                    'error' => $e->getMessage(),
                ]);
                throw $e;
            }
        }

        return $queuedItems;
    }
}
