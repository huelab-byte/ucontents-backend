<?php

declare(strict_types=1);

namespace Modules\MediaUpload\Actions;

use Modules\MediaUpload\Services\VideoProcessingService;
use Illuminate\Support\Facades\Log;

class ExtractAndMergeFramesAction
{
    public function __construct(
        private VideoProcessingService $videoService
    ) {}

    /**
     * Extract 6 frames from video, merge into one image, return path to merged image.
     * Caller must delete the merged file when done.
     */
    public function execute(string $videoPath, ?int $frameCount = null): string
    {
        $framePaths = $this->videoService->extractFrames($videoPath, $frameCount ?? 6);
        if (empty($framePaths)) {
            throw new \RuntimeException('No frames extracted from video');
        }
        $props = $this->videoService->getVideoProperties($videoPath);
        $orientation = $props['orientation'] ?? 'horizontal';
        $merged = $this->videoService->mergeFrames($framePaths, $orientation);
        $this->videoService->cleanup($framePaths);
        return $merged;
    }
}
