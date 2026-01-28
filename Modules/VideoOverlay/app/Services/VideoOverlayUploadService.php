<?php

declare(strict_types=1);

namespace Modules\VideoOverlay\Services;

use Illuminate\Http\UploadedFile;
use Modules\VideoOverlay\Actions\UploadVideoOverlayAction;

class VideoOverlayUploadService
{
    public function __construct(
        private UploadVideoOverlayAction $uploadAction
    ) {}

    /**
     * Upload a single video overlay file
     */
    public function upload(
        UploadedFile $file,
        ?int $folderId = null,
        ?string $title = null
    ) {
        return $this->uploadAction->execute($file, $folderId, $title);
    }
}
