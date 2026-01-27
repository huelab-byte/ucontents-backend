<?php

declare(strict_types=1);

namespace Modules\AudioLibrary\Services;

use Illuminate\Http\UploadedFile;
use Modules\AudioLibrary\Actions\UploadAudioAction;
use Modules\AudioLibrary\Actions\BulkUploadAudioAction;
use Modules\AudioLibrary\Models\AudioUploadQueue;

class AudioUploadService
{
    public function __construct(
        private UploadAudioAction $uploadAction,
        private BulkUploadAudioAction $bulkUploadAction
    ) {}

    /**
     * Upload a single audio file
     */
    public function upload(
        UploadedFile $file,
        ?int $folderId = null,
        ?string $title = null
    ) {
        return $this->uploadAction->execute($file, $folderId, $title);
    }

    /**
     * Queue multiple audio files for upload
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
    public function getQueueStatus(int $queueId): ?AudioUploadQueue
    {
        return AudioUploadQueue::find($queueId);
    }
}
