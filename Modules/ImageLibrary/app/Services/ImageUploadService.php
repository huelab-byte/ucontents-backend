<?php

declare(strict_types=1);

namespace Modules\ImageLibrary\Services;

use Illuminate\Http\UploadedFile;
use Modules\ImageLibrary\Actions\UploadImageAction;
use Modules\ImageLibrary\Actions\BulkUploadImageAction;
use Modules\ImageLibrary\Models\ImageUploadQueue;

class ImageUploadService
{
    public function __construct(
        private UploadImageAction $uploadAction,
        private BulkUploadImageAction $bulkUploadAction
    ) {}

    /**
     * Upload a single image file
     */
    public function upload(
        UploadedFile $file,
        ?int $folderId = null,
        ?string $title = null
    ) {
        return $this->uploadAction->execute($file, $folderId, $title);
    }

    /**
     * Queue multiple image files for upload
     */
    public function bulkUpload(
        array $files,
        ?int $folderId = null
    ): array {
        return $this->bulkUploadAction->execute($files, $folderId);
    }

    /**
     * Get upload queue status
     */
    public function getQueueStatus(int $queueId): ?ImageUploadQueue
    {
        return ImageUploadQueue::find($queueId);
    }
}
