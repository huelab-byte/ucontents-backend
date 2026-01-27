<?php

declare(strict_types=1);

namespace Modules\FootageLibrary\Actions;

use Modules\FootageLibrary\Services\VideoProcessingService;
use Illuminate\Support\Facades\Log;

class ExtractFramesAction
{
    public function __construct(
        private VideoProcessingService $videoService
    ) {}

    /**
     * Extract frames from video and merge them
     */
    public function execute(string $videoPath, ?int $frameCount = null): array
    {
        try {
            // Extract frames
            $framePaths = $this->videoService->extractFrames($videoPath, $frameCount);
            
            if (empty($framePaths)) {
                throw new \RuntimeException('No frames extracted from video');
            }

            // Get video properties to determine orientation
            $properties = $this->videoService->getVideoProperties($videoPath);
            $orientation = $properties['orientation'] ?? 'horizontal';

            // Merge frames into single image
            $mergedPath = $this->videoService->mergeFrames($framePaths, $orientation);

            // Cleanup individual frames
            $this->videoService->cleanup($framePaths);

            return [
                'merged_frame_path' => $mergedPath,
                'frame_count' => count($framePaths),
                'orientation' => $orientation,
                'properties' => $properties,
            ];
        } catch (\Exception $e) {
            Log::error('Failed to extract frames', [
                'video_path' => $videoPath,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
