<?php

declare(strict_types=1);

namespace Modules\FootageLibrary\Services;

use Modules\FootageLibrary\Actions\GenerateMetadataFromTitleAction;
use Modules\FootageLibrary\Actions\GenerateMetadataFromFramesAction;
use Modules\FootageLibrary\Actions\ExtractFramesAction;
use Modules\FootageLibrary\Services\VideoProcessingService;
use Illuminate\Support\Facades\Log;

class MetadataGenerationService
{
    public function __construct(
        private GenerateMetadataFromTitleAction $generateFromTitleAction,
        private GenerateMetadataFromFramesAction $generateFromFramesAction,
        private ExtractFramesAction $extractFramesAction,
        private VideoProcessingService $videoService
    ) {}

    /**
     * Generate metadata from title
     */
    public function generateFromTitle(string $title, ?int $userId = null): array
    {
        return $this->generateFromTitleAction->execute($title, $userId);
    }

    /**
     * Generate metadata from frames
     */
    public function generateFromFrames(string $videoPath, string $title, ?int $userId = null): array
    {
        try {
            // Extract and merge frames
            $extractionResult = $this->extractFramesAction->execute($videoPath);
            $mergedFramePath = $extractionResult['merged_frame_path'];
            
            try {
                // Generate metadata from merged frames
                $metadata = $this->generateFromFramesAction->execute($mergedFramePath, $title, $userId);
                
                // Add video properties to metadata
                $metadata['duration'] = $extractionResult['properties']['duration'] ?? 0.0;
                $metadata['resolution'] = [
                    'width' => $extractionResult['properties']['width'] ?? 0,
                    'height' => $extractionResult['properties']['height'] ?? 0,
                ];
                $metadata['fps'] = $extractionResult['properties']['fps'] ?? 30.0;
                $metadata['format'] = $extractionResult['properties']['format'] ?? 'mp4';
                
                return $metadata;
            } finally {
                // Cleanup merged frame
                if (file_exists($mergedFramePath)) {
                    unlink($mergedFramePath);
                }
            }
        } catch (\Exception $e) {
            Log::error('Failed to generate metadata from frames', [
                'video_path' => $videoPath,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
