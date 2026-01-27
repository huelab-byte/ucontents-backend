<?php

declare(strict_types=1);

namespace Modules\FootageLibrary\Services;

use Illuminate\Http\UploadedFile;
use Modules\FootageLibrary\Actions\UploadFootageAction;
use Modules\FootageLibrary\Actions\BulkUploadFootageAction;
use Modules\FootageLibrary\Models\FootageUploadQueue;

class FootageUploadService
{
    public function __construct(
        private UploadFootageAction $uploadAction,
        private BulkUploadFootageAction $bulkUploadAction
    ) {}

    /**
     * Upload a single footage file
     */
    public function upload(
        UploadedFile $file,
        ?int $folderId = null,
        ?string $title = null,
        string $metadataSource = 'title'
    ) {
        return $this->uploadAction->execute($file, $folderId, $title, $metadataSource);
    }

    /**
     * Queue multiple footage files for upload
     */
    public function bulkUpload(
        array $files,
        ?int $folderId = null,
        string $metadataSource = 'title'
    ): array {
        return $this->bulkUploadAction->execute($files, $folderId, $metadataSource);
    }

    /**
     * Get upload queue status
     */
    public function getQueueStatus(int $queueId): ?FootageUploadQueue
    {
        return FootageUploadQueue::find($queueId);
    }
}
