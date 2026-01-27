<?php

declare(strict_types=1);

namespace Modules\StorageManagement\Services;

use Illuminate\Http\UploadedFile;
use Modules\StorageManagement\Actions\UploadFileAction;
use Modules\StorageManagement\Models\StorageUploadQueue;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Log;

/**
 * File Upload Service
 * 
 * This service provides common file upload functionality that can be used
 * throughout the application. It supports:
 * - Single file uploads
 * - Bulk file uploads
 * - Queue-based uploads for large files
 */
class FileUploadService
{
    public function __construct(
        private UploadFileAction $uploadAction
    ) {}

    /**
     * Upload a single file
     *
     * @param UploadedFile $file
     * @param string|null $path Custom path
     * @param mixed $reference Reference model (polymorphic)
     * @return \Modules\StorageManagement\Models\StorageFile
     */
    public function upload(UploadedFile $file, ?string $path = null, $reference = null)
    {
        return $this->uploadAction->execute($file, $path, auth()->id(), $reference);
    }

    /**
     * Upload multiple files (bulk upload)
     *
     * @param array<UploadedFile> $files
     * @param string|null $path Base path
     * @param mixed $reference Reference model
     * @return array ['uploaded' => [], 'failed' => []]
     */
    public function bulkUpload(array $files, ?string $path = null, $reference = null): array
    {
        $uploaded = [];
        $failed = [];

        foreach ($files as $file) {
            try {
                $storageFile = $this->uploadAction->execute($file, $path, auth()->id(), $reference);
                $uploaded[] = $storageFile;
            } catch (\Exception $e) {
                $failed[] = [
                    'file' => $file->getClientOriginalName(),
                    'error' => $e->getMessage(),
                ];
                Log::error('Bulk upload failed for file', [
                    'file' => $file->getClientOriginalName(),
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return [
            'uploaded' => $uploaded,
            'failed' => $failed,
            'total' => count($files),
            'success_count' => count($uploaded),
            'failed_count' => count($failed),
        ];
    }

    /**
     * Queue a file for upload (for large files or async processing)
     *
     * @param UploadedFile $file
     * @param string|null $path
     * @param mixed $reference
     * @return StorageUploadQueue
     */
    public function queueUpload(UploadedFile $file, ?string $path = null, $reference = null): StorageUploadQueue
    {
        // Store file temporarily
        $tempPath = $file->store('temp/uploads');
        
        $queueItem = StorageUploadQueue::create([
            'user_id' => auth()->id(),
            'file_name' => $file->getClientOriginalName(),
            'file_path' => $tempPath,
            'file_size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'status' => 'pending',
            'metadata' => [
                'path' => $path,
                'reference_type' => $reference ? get_class($reference) : null,
                'reference_id' => $reference?->id,
            ],
        ]);

        // Dispatch job to process upload
        Queue::push(\Modules\StorageManagement\Jobs\ProcessFileUploadJob::class, [
            'queue_id' => $queueItem->id,
        ]);

        return $queueItem;
    }

    /**
     * Get upload queue status
     *
     * @param int $queueId
     * @return StorageUploadQueue|null
     */
    public function getQueueStatus(int $queueId): ?StorageUploadQueue
    {
        return StorageUploadQueue::find($queueId);
    }
}
