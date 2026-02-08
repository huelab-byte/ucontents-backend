<?php

declare(strict_types=1);

namespace Modules\MediaUpload\Actions;

use Illuminate\Http\UploadedFile;
use Modules\MediaUpload\Models\MediaUploadQueue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class BulkUploadAction
{
    /**
     * @param  array<int, \Illuminate\Http\UploadedFile>  $files
     * @param  int|array<int, int>  $folderIdOrIds  Single folder id for all files, or one folder id per file (same order as $files)
     */
    public function execute(array $files, int|array $folderIdOrIds, ?int $userId = null, ?array $captionConfig = null): array
    {
        $userId = $userId ?? auth()->id();
        $tempPath = config('mediaupload.module.upload.temp_path', 'temp/media-uploads');
        $queuedItems = [];
        $isPerFileFolders = is_array($folderIdOrIds);

        $disk = Storage::disk('local');
        foreach ($files as $index => $file) {
            $folderId = $isPerFileFolders ? (int) $folderIdOrIds[$index] : (int) $folderIdOrIds;
            try {
                $path = $file->store($tempPath, 'local');
                if (!$path || !$disk->exists($path)) {
                    Log::error('BulkUpload: file not stored', ['name' => $file->getClientOriginalName()]);
                    throw new \RuntimeException('Failed to store file: ' . $file->getClientOriginalName());
                }
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

                // Dispatch immediately so file enters processing queue as soon as it is stored
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
