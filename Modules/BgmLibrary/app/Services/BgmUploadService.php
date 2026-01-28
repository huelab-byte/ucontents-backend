<?php

declare(strict_types=1);

namespace Modules\BgmLibrary\Services;

use Illuminate\Http\UploadedFile;
use Modules\BgmLibrary\Actions\UploadBgmAction;
use Modules\BgmLibrary\Actions\BulkUploadBgmAction;
use Modules\BgmLibrary\Models\BgmUploadQueue;

class BgmUploadService
{
    public function __construct(
        private UploadBgmAction $uploadAction,
        private BulkUploadBgmAction $bulkUploadAction
    ) {}

    /**
     * Upload a single BGM file
     */
    public function upload(
        UploadedFile $file,
        ?int $folderId = null,
        ?string $title = null
    ) {
        return $this->uploadAction->execute($file, $folderId, $title);
    }

    /**
     * Queue multiple BGM files for upload
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
    public function getQueueStatus(int $queueId): ?BgmUploadQueue
    {
        return BgmUploadQueue::find($queueId);
    }
}
