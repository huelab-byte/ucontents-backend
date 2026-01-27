<?php

declare(strict_types=1);

namespace Modules\FootageLibrary\Services;

use FFMpeg\FFMpeg;
use FFMpeg\FFProbe;
use FFMpeg\Coordinate\TimeCode;
use FFMpeg\Format\Video\X264;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Symfony\Component\Process\Process;

class VideoProcessingService
{
    private FFMpeg $ffmpeg;
    private FFProbe $ffprobe;
    private ImageManager $imageManager;
    private int $frameCount;

    public function __construct()
    {
        $ffmpegBinaries = config('ffmpeg.ffmpeg.binaries', 'ffmpeg');
        $ffprobeBinaries = config('ffmpeg.ffprobe.binaries', 'ffprobe');
        $timeout = config('footagelibrary.module.video.ffmpeg.timeout', config('ffmpeg.timeout', 300));
        $threads = config('footagelibrary.module.video.ffmpeg.threads', config('ffmpeg.threads', 2));

        $this->ffmpeg = FFMpeg::create([
            'ffmpeg.binaries' => $ffmpegBinaries,
            'ffprobe.binaries' => $ffprobeBinaries,
            'timeout' => (int) $timeout,
            'ffmpeg.threads' => (int) $threads,
        ]);
        
        $this->ffprobe = FFProbe::create([
            'ffprobe.binaries' => $ffprobeBinaries,
        ]);

        $this->imageManager = new ImageManager(new Driver());
        $this->frameCount = config('footagelibrary.module.video.frame_extraction.count', 6);
        
        // Ensure temp directories exist
        $this->ensureTempDirectoriesExist();
    }

    /**
     * Ensure temp directories exist
     */
    private function ensureTempDirectoriesExist(): void
    {
        $directories = [
            storage_path('app/temp'),
            storage_path('app/temp/frames'),
        ];
        
        foreach ($directories as $dir) {
            $dir = $this->normalizePath($dir);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
        }
    }

    /**
     * Normalize path for cross-platform compatibility
     */
    private function normalizePath(string $path): string
    {
        // Convert forward slashes to backslashes on Windows, or backslashes to forward slashes on Unix
        if (DIRECTORY_SEPARATOR === '\\') {
            return str_replace('/', '\\', $path);
        }
        return str_replace('\\', '/', $path);
    }

    /**
     * Get video duration in seconds
     */
    public function getDuration(string $videoPath): float
    {
        $videoPath = $this->normalizePath($videoPath);
        
        try {
            $video = $this->ffmpeg->open($videoPath);
            return (float) $video->getStreams()->videos()->first()->get('duration');
        } catch (\Exception $e) {
            Log::error('Failed to get video duration', [
                'path' => $videoPath,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
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
     * Extract frames from video
     * Returns array of frame file paths
     */
    public function extractFrames(string $videoPath, ?int $frameCount = null): array
    {
        $videoPath = $this->normalizePath($videoPath);
        $frameCount = $frameCount ?? $this->frameCount;
        $framePaths = [];

        try {
            $duration = $this->getDuration($videoPath);
            
            // Calculate frame extraction times
            $times = $this->calculateFrameTimes($duration, $frameCount);
            
            // Create temp directory with normalized path
            $tempDir = $this->normalizePath(storage_path('app/temp/frames/' . uniqid()));
            if (!is_dir($tempDir)) {
                if (!mkdir($tempDir, 0755, true) && !is_dir($tempDir)) {
                    throw new \RuntimeException('Failed to create temp directory: ' . $tempDir);
                }
            }

            Log::info('Extracting frames', [
                'video_path' => $videoPath,
                'temp_dir' => $tempDir,
                'frame_count' => $frameCount,
                'duration' => $duration,
            ]);

            $ffmpegBinaries = config('ffmpeg.ffmpeg.binaries', 'ffmpeg');

            foreach ($times as $index => $time) {
                $framePath = $this->normalizePath($tempDir . DIRECTORY_SEPARATOR . 'frame_' . $index . '.jpg');
                
                // Use direct FFmpeg command for better Windows compatibility
                // -pix_fmt yuvj420p ensures full-range YUV for JPEG output
                $process = new Process([
                    $ffmpegBinaries,
                    '-ss', (string) $time,
                    '-i', $videoPath,
                    '-vframes', '1',
                    '-pix_fmt', 'yuvj420p',
                    '-q:v', '2',
                    '-y',
                    $framePath
                ]);
                
                $process->setTimeout(60);
                $process->run();
                
                if (!$process->isSuccessful()) {
                    Log::error('FFmpeg frame extraction failed', [
                        'command' => $process->getCommandLine(),
                        'error' => $process->getErrorOutput(),
                        'output' => $process->getOutput(),
                    ]);
                    throw new \RuntimeException('FFmpeg failed to extract frame: ' . $process->getErrorOutput());
                }
                
                if (!file_exists($framePath)) {
                    throw new \RuntimeException('Frame was not saved: ' . $framePath);
                }
                
                $framePaths[] = $framePath;
            }

            return $framePaths;
        } catch (\Exception $e) {
            Log::error('Failed to extract frames', [
                'path' => $videoPath,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            // Cleanup on error
            foreach ($framePaths as $path) {
                if (file_exists($path)) {
                    unlink($path);
                }
            }
            
            throw $e;
        }
    }

    /**
     * Merge frames into a single image
     * Creates a grid layout (horizontal or vertical)
     */
    public function mergeFrames(array $framePaths, string $orientation = 'horizontal'): string
    {
        if (empty($framePaths)) {
            throw new \InvalidArgumentException('No frames to merge');
        }

        try {
            $images = [];
            foreach ($framePaths as $path) {
                if (file_exists($path)) {
                    $images[] = $this->imageManager->read($path);
                }
            }

            if (empty($images)) {
                throw new \RuntimeException('No valid frame images found');
            }

            $firstImage = $images[0];
            $frameWidth = $firstImage->width();
            $frameHeight = $firstImage->height();

            if ($orientation === 'horizontal') {
                // Horizontal grid: 2 rows x 3 columns
                $cols = 3;
                $rows = (int) ceil(count($images) / $cols);
                $mergedWidth = $frameWidth * $cols;
                $mergedHeight = $frameHeight * $rows;
            } else {
                // Vertical grid: 3 rows x 2 columns
                $rows = 3;
                $cols = (int) ceil(count($images) / $rows);
                $mergedWidth = $frameWidth * $cols;
                $mergedHeight = $frameHeight * $rows;
            }

            $merged = $this->imageManager->create($mergedWidth, $mergedHeight);

            foreach ($images as $index => $image) {
                $row = (int) floor($index / $cols);
                $col = $index % $cols;
                $x = $col * $frameWidth;
                $y = $row * $frameHeight;
                
                $merged->place($image, 'top-left', $x, $y);
            }

            $outputPath = $this->normalizePath(storage_path('app/temp/merged_' . uniqid() . '.jpg'));
            
            // Ensure temp directory exists
            $tempDir = dirname($outputPath);
            if (!is_dir($tempDir)) {
                mkdir($tempDir, 0755, true);
            }
            
            $merged->save($outputPath);

            return $outputPath;
        } catch (\Exception $e) {
            Log::error('Failed to merge frames', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Calculate frame extraction times
     * Distributes frames evenly across the video, avoiding the exact end
     */
    private function calculateFrameTimes(float $duration, int $frameCount): array
    {
        if ($frameCount <= 1) {
            return [0.0];
        }

        // Use 95% of duration to avoid seeking to exact end where no frame may exist
        $safeDuration = $duration * 0.95;
        $times = [];
        $interval = $safeDuration / ($frameCount - 1);

        for ($i = 0; $i < $frameCount; $i++) {
            $time = $i * $interval;
            $times[] = $time;
        }

        return $times;
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

    /**
     * Cleanup temporary files
     */
    public function cleanup(array $paths): void
    {
        foreach ($paths as $path) {
            if (file_exists($path)) {
                unlink($path);
            }
        }
    }
}
