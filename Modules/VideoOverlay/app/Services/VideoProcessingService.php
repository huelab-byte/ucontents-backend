<?php

declare(strict_types=1);

namespace Modules\VideoOverlay\Services;

use FFMpeg\FFMpeg;
use FFMpeg\FFProbe;
use Illuminate\Support\Facades\Log;

class VideoProcessingService
{
    private FFMpeg $ffmpeg;
    private FFProbe $ffprobe;

    public function __construct()
    {
        $ffmpegBinaries = config('ffmpeg.ffmpeg.binaries', 'ffmpeg');
        $ffprobeBinaries = config('ffmpeg.ffprobe.binaries', 'ffprobe');
        $timeout = config('videooverlay.module.video.ffmpeg.timeout', config('ffmpeg.timeout', 300));
        $threads = config('videooverlay.module.video.ffmpeg.threads', config('ffmpeg.threads', 2));

        $this->ffmpeg = FFMpeg::create([
            'ffmpeg.binaries' => $ffmpegBinaries,
            'ffprobe.binaries' => $ffprobeBinaries,
            'timeout' => (int) $timeout,
            'ffmpeg.threads' => (int) $threads,
        ]);
        
        $this->ffprobe = FFProbe::create([
            'ffprobe.binaries' => $ffprobeBinaries,
        ]);
    }

    /**
     * Normalize path for cross-platform compatibility
     */
    private function normalizePath(string $path): string
    {
        if (DIRECTORY_SEPARATOR === '\\') {
            return str_replace('/', '\\', $path);
        }
        return str_replace('\\', '/', $path);
    }

    /**
     * Get video properties
     */
    public function getVideoProperties(string $videoPath): array
    {
        $videoPath = $this->normalizePath($videoPath);
        
        try {
            $video = $this->ffmpeg->open($videoPath);
            $videoStream = $video->getStreams()->videos()->first();
            
            return [
                'duration' => (float) $videoStream->get('duration'),
                'width' => (int) $videoStream->get('width'),
                'height' => (int) $videoStream->get('height'),
                'fps' => (float) $this->calculateFps($videoStream),
                'format' => pathinfo($videoPath, PATHINFO_EXTENSION),
                'orientation' => $this->determineOrientation(
                    (int) $videoStream->get('width'),
                    (int) $videoStream->get('height')
                ),
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get video properties', [
                'path' => $videoPath,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Calculate FPS from video stream
     */
    private function calculateFps($videoStream): float
    {
        $rFrameRate = $videoStream->get('r_frame_rate');
        if ($rFrameRate && strpos($rFrameRate, '/') !== false) {
            [$numerator, $denominator] = explode('/', $rFrameRate);
            if ($denominator > 0) {
                return (float) $numerator / (float) $denominator;
            }
        }
        return 30.0; // Default FPS
    }

    /**
     * Determine video orientation
     */
    private function determineOrientation(int $width, int $height): string
    {
        return $width >= $height ? 'horizontal' : 'vertical';
    }
}
