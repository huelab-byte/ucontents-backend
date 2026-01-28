<?php

declare(strict_types=1);

namespace Modules\ImageOverlay\Services;

use Illuminate\Http\UploadedFile;
use Modules\ImageOverlay\Actions\UploadImageOverlayAction;
use Modules\ImageOverlay\Actions\BulkUploadImageOverlayAction;
use Modules\ImageOverlay\Models\ImageOverlayUploadQueue;

class ImageOverlayUploadService
{
    public function __construct(
        private UploadImageOverlayAction $uploadAction,
        private BulkUploadImageOverlayAction $bulkUploadAction
    ) {}

    /**
     * Upload a single image overlay file
     */
    public function upload(
        UploadedFile $file,
        ?int $folderId = null,
        ?string $title = null
    ) {
        return $this->uploadAction->execute($file, $folderId, $title);
    }

    /**
     * Queue multiple image overlay files for upload
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
    public function getQueueStatus(int $queueId): ?ImageOverlayUploadQueue
    {
        return ImageOverlayUploadQueue::find($queueId);
    }
}
