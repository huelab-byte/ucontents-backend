<?php

declare(strict_types=1);

namespace Modules\MediaUpload\Services;

use FFMpeg\FFMpeg;
use FFMpeg\FFProbe;
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
        $timeout = config('mediaupload.module.video.ffmpeg.timeout', config('ffmpeg.timeout', 300));
        $threads = config('mediaupload.module.video.ffmpeg.threads', config('ffmpeg.threads', 2));

        $this->ffmpeg = FFMpeg::create([
            'ffmpeg.binaries' => $ffmpegBinaries,
            'ffprobe.binaries' => $ffprobeBinaries,
            'timeout' => (int) $timeout,
            'ffmpeg.threads' => (int) $threads,
        ]);
        $this->ffprobe = FFProbe::create(['ffprobe.binaries' => $ffprobeBinaries]);
        $this->imageManager = new ImageManager(new Driver());
        $this->frameCount = config('mediaupload.module.video.frame_extraction.count', 6);
        $this->ensureTempDirectoriesExist();
    }

    private function ensureTempDirectoriesExist(): void
    {
        $dirs = [storage_path('app/temp'), storage_path('app/temp/frames')];
        foreach ($dirs as $dir) {
            $d = $this->normalizePath($dir);
            if (!is_dir($d)) {
                mkdir($d, 0755, true);
            }
        }
    }

    private function normalizePath(string $path): string
    {
        return DIRECTORY_SEPARATOR === '\\' ? str_replace('/', '\\', $path) : str_replace('\\', '/', $path);
    }

    public function getDuration(string $videoPath): float
    {
        $videoPath = $this->normalizePath($videoPath);
        $video = $this->ffmpeg->open($videoPath);
        return (float) $video->getStreams()->videos()->first()->get('duration');
    }

    public function getVideoProperties(string $videoPath): array
    {
        $videoPath = $this->normalizePath($videoPath);
        $video = $this->ffmpeg->open($videoPath);
        $vs = $video->getStreams()->videos()->first();
        $w = (int) $vs->get('width');
        $h = (int) $vs->get('height');
        $r = $vs->get('r_frame_rate');
        $fps = 30.0;
        if ($r && str_contains($r, '/')) {
            [$n, $d] = explode('/', $r);
            if ((float) $d > 0) {
                $fps = (float) $n / (float) $d;
            }
        }
        return [
            'duration' => (float) $vs->get('duration'),
            'width' => $w,
            'height' => $h,
            'fps' => $fps,
            'format' => pathinfo($videoPath, PATHINFO_EXTENSION),
            'orientation' => $w >= $h ? 'horizontal' : 'vertical',
        ];
    }

    public function extractFrames(string $videoPath, ?int $frameCount = null): array
    {
        $videoPath = $this->normalizePath($videoPath);
        $n = $frameCount ?? $this->frameCount;
        $dur = $this->getDuration($videoPath);
        $safe = $dur * 0.95;
        $interval = $n <= 1 ? 0 : $safe / ($n - 1);
        $times = [];
        for ($i = 0; $i < $n; $i++) {
            $times[] = $i * $interval;
        }
        $tmpDir = $this->normalizePath(storage_path('app/temp/frames/' . uniqid()));
        mkdir($tmpDir, 0755, true);
        $bin = config('ffmpeg.ffmpeg.binaries', 'ffmpeg');
        $paths = [];
        foreach ($times as $i => $t) {
            $fp = $this->normalizePath($tmpDir . DIRECTORY_SEPARATOR . 'frame_' . $i . '.jpg');
            $proc = new Process([$bin, '-ss', (string) $t, '-i', $videoPath, '-vframes', '1', '-pix_fmt', 'yuvj420p', '-q:v', '2', '-y', $fp]);
            $proc->setTimeout(60);
            $proc->run();
            if (!$proc->isSuccessful()) {
                $this->cleanup($paths);
                throw new \RuntimeException('FFmpeg frame extract failed: ' . $proc->getErrorOutput());
            }
            $paths[] = $fp;
        }
        return $paths;
    }

    public function mergeFrames(array $framePaths, string $orientation = 'horizontal'): string
    {
        if (empty($framePaths)) {
            throw new \InvalidArgumentException('No frames to merge');
        }
        $images = [];
        foreach ($framePaths as $p) {
            if (file_exists($p)) {
                $images[] = $this->imageManager->read($p);
            }
        }
        if (empty($images)) {
            throw new \RuntimeException('No valid frame images');
        }
        $fw = $images[0]->width();
        $fh = $images[0]->height();
        $cols = $orientation === 'horizontal' ? 3 : (int) ceil(count($images) / 3);
        $rows = (int) ceil(count($images) / $cols);
        $mw = $fw * $cols;
        $mh = $fh * $rows;
        $merged = $this->imageManager->create($mw, $mh);
        foreach ($images as $i => $img) {
            $row = (int) floor($i / $cols);
            $col = $i % $cols;
            $merged->place($img, 'top-left', $col * $fw, $row * $fh);
        }
        $out = $this->normalizePath(storage_path('app/temp/merged_' . uniqid() . '.jpg'));
        $merged->save($out);
        return $out;
    }

    public function cleanup(array $paths): void
    {
        foreach ($paths as $p) {
            if (file_exists($p)) {
                @unlink($p);
            }
        }
    }

    /**
     * Process video: optional loop, optional reverse (regular then reverse once).
     * Returns path to output file.
     * 
     * Loop: If loopCount = 3, the video will repeat 3 times (total duration = original * 3)
     * Reverse: Appends a reversed version of the video at the end
     */
    public function processVideo(string $inputPath, string $outputPath, int $loopCount = 1, bool $enableReverse = false): string
    {
        $inputPath = $this->normalizePath($inputPath);
        $outputPath = $this->normalizePath($outputPath);
        $bin = config('ffmpeg.ffmpeg.binaries', 'ffmpeg');
        $dir = dirname($outputPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $current = $inputPath;
        $tempFiles = [];

        try {
            // Step 1: Loop the video if loopCount > 1
            // Using concat demuxer for reliable looping (more compatible than -stream_loop)
            if ($loopCount > 1) {
                $looped = $dir . DIRECTORY_SEPARATOR . 'mu_loop_' . uniqid() . '.mp4';
                
                // First, re-encode input to ensure consistent format for concatenation
                $normalized = $dir . DIRECTORY_SEPARATOR . 'mu_norm_' . uniqid() . '.mp4';
                $normalizeProc = new Process([
                    $bin, '-i', $current,
                    '-c:v', 'libx264', '-preset', 'fast', '-crf', '23',
                    '-c:a', 'aac', '-b:a', '128k',
                    '-y', $normalized
                ]);
                $normalizeProc->setTimeout((int) config('ffmpeg.timeout', 300));
                $normalizeProc->run();
                if (!$normalizeProc->isSuccessful()) {
                    throw new \RuntimeException('FFmpeg normalize failed: ' . $normalizeProc->getErrorOutput());
                }
                $tempFiles[] = $normalized;
                
                // Create concat list file with the video repeated loopCount times
                $loopList = $dir . DIRECTORY_SEPARATOR . 'mu_loop_list_' . uniqid() . '.txt';
                $escapedPath = str_replace("'", "'\\''", $normalized);
                $listContent = '';
                for ($i = 0; $i < $loopCount; $i++) {
                    $listContent .= "file '{$escapedPath}'\n";
                }
                file_put_contents($loopList, $listContent);
                $tempFiles[] = $loopList;
                
                // Concatenate using concat demuxer
                $loopProc = new Process([
                    $bin, '-f', 'concat', '-safe', '0', '-i', $loopList,
                    '-c', 'copy', '-y', $looped
                ]);
                $loopProc->setTimeout((int) config('ffmpeg.timeout', 300));
                $loopProc->run();
                if (!$loopProc->isSuccessful()) {
                    throw new \RuntimeException('FFmpeg loop concat failed: ' . $loopProc->getErrorOutput());
                }
                
                $current = $looped;
                $tempFiles[] = $looped;
            }

            // Step 2: Reverse and append if enableReverse is true
            if ($enableReverse) {
                $reversed = $dir . DIRECTORY_SEPARATOR . 'mu_rev_' . uniqid() . '.mp4';
                
                // Create reversed video (this requires re-encoding)
                $reverseProc = new Process([
                    $bin, '-i', $current,
                    '-vf', 'reverse',
                    '-af', 'areverse',
                    '-c:v', 'libx264', '-preset', 'fast', '-crf', '23',
                    '-c:a', 'aac', '-b:a', '128k',
                    '-y', $reversed,
                ]);
                $reverseProc->setTimeout((int) config('ffmpeg.timeout', 300));
                $reverseProc->run();
                if (!$reverseProc->isSuccessful()) {
                    throw new \RuntimeException('FFmpeg reverse failed: ' . $reverseProc->getErrorOutput());
                }
                $tempFiles[] = $reversed;
                
                // Re-encode current video to ensure same format for concat
                $currentNormalized = $dir . DIRECTORY_SEPARATOR . 'mu_curr_norm_' . uniqid() . '.mp4';
                $currNormProc = new Process([
                    $bin, '-i', $current,
                    '-c:v', 'libx264', '-preset', 'fast', '-crf', '23',
                    '-c:a', 'aac', '-b:a', '128k',
                    '-y', $currentNormalized
                ]);
                $currNormProc->setTimeout((int) config('ffmpeg.timeout', 300));
                $currNormProc->run();
                if (!$currNormProc->isSuccessful()) {
                    throw new \RuntimeException('FFmpeg current normalize failed: ' . $currNormProc->getErrorOutput());
                }
                $tempFiles[] = $currentNormalized;
                
                // Create concat list: original + reversed
                $concatList = $dir . DIRECTORY_SEPARATOR . 'mu_concat_' . uniqid() . '.txt';
                $listContent = "file '" . str_replace("'", "'\\''", $currentNormalized) . "'\n";
                $listContent .= "file '" . str_replace("'", "'\\''", $reversed) . "'";
                file_put_contents($concatList, $listContent);
                $tempFiles[] = $concatList;

                $concatProc = new Process([
                    $bin, '-f', 'concat', '-safe', '0', '-i', $concatList,
                    '-c', 'copy', '-y', $outputPath
                ]);
                $concatProc->setTimeout((int) config('ffmpeg.timeout', 300));
                $concatProc->run();
                if (!$concatProc->isSuccessful()) {
                    throw new \RuntimeException('FFmpeg concat failed: ' . $concatProc->getErrorOutput());
                }
            } else {
                // No reverse - just copy current to output
                if ($current === $inputPath) {
                    // No looping was done, just copy the input
                    $proc = new Process([$bin, '-i', $current, '-c', 'copy', '-y', $outputPath]);
                    $proc->setTimeout((int) config('ffmpeg.timeout', 300));
                    $proc->run();
                    if (!$proc->isSuccessful()) {
                        throw new \RuntimeException('FFmpeg copy failed: ' . $proc->getErrorOutput());
                    }
                } else {
                    // Looping was done, move temp file to output
                    rename($current, $outputPath);
                    // Remove from tempFiles since we renamed it
                    $tempFiles = array_filter($tempFiles, fn($f) => $f !== $current);
                }
            }

            return $outputPath;
        } finally {
            foreach ($tempFiles as $f) {
                if (file_exists($f)) {
                    @unlink($f);
                }
            }
        }
    }
}
