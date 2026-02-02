<?php

declare(strict_types=1);

namespace Modules\MediaUpload\Services;

use Modules\MediaUpload\Models\CaptionTemplate;
use Symfony\Component\Process\Process;

/**
 * Burns captions into video using FFmpeg ASS subtitles.
 */
class CaptionBurnService
{
    /**
     * Hex color to ASS format &HAABBGGRR (BGR order).
     */
    private function hexToAssColor(string $hex): string
    {
        $hex = ltrim($hex, '#');
        if (strlen($hex) === 6) {
            $r = hexdec(substr($hex, 0, 2));
            $g = hexdec(substr($hex, 2, 2));
            $b = hexdec(substr($hex, 4, 2));
        } else {
            $r = $g = $b = 255;
        }
        return sprintf('&H00%02X%02X%02X', $b, $g, $r);
    }

    /**
     * ASS alignment: top=8, center=5, bottom=2.
     */
    private function positionToAlignment(string $position): int
    {
        return match (strtolower($position)) {
            'top' => 8,
            'center' => 5,
            default => 2, // bottom
        };
    }

    /**
     * Build default style values when no template.
     */
    private function defaultStyle(): array
    {
        return [
            'font' => 'Arial',
            'font_size' => 32,
            'font_weight' => 'regular',
            'font_color' => '#FFFFFF',
            'outline_color' => '#000000',
            'outline_size' => 3,
            'position' => 'bottom',
            'position_offset' => 30,
        ];
    }

    /**
     * Map font_weight to ASS Bold and Italic (0 or 1).
     */
    private function fontWeightToBoldItalic(string $fontWeight): array
    {
        return match (strtolower($fontWeight)) {
            'bold' => [1, 0],
            'italic' => [0, 1],
            'bold_italic' => [1, 1],
            'black' => [1, 0],
            default => [0, 0],
        };
    }

    /**
     * Split caption text into chunks by words_per_caption.
     */
    private function splitCaption(string $text, int $wordsPerCaption): array
    {
        $words = preg_split('/\s+/u', trim($text), -1, PREG_SPLIT_NO_EMPTY);
        if (empty($words)) {
            return [];
        }
        $wordsPerCaption = max(1, $wordsPerCaption);
        $chunks = [];
        for ($i = 0; $i < count($words); $i += $wordsPerCaption) {
            $chunk = array_slice($words, $i, $wordsPerCaption);
            $chunks[] = implode(' ', $chunk);
        }
        return $chunks;
    }

    /**
     * Generate ASS file content.
     * @param CaptionTemplate|array|null $templateOrConfig Template model, config array (font, font_size, font_color, etc.), or null for defaults
     * @param int $playResX Video width (must match video for correct positioning)
     * @param int $playResY Video height (must match video for correct positioning)
     */
    public function generateAssFile(
        string $captionText,
        float $durationSeconds,
        CaptionTemplate|array|null $templateOrConfig = null,
        int $playResX = 1920,
        int $playResY = 1080
    ): string {
        $style = $this->resolveStyle($templateOrConfig);
        $wordsPerCaption = $this->resolveWordsPerCaption($templateOrConfig);
        $chunks = $this->splitCaption($captionText, $wordsPerCaption);
        if (empty($chunks)) {
            return '';
        }

        $primaryColour = $this->hexToAssColor($style['font_color']);
        $outlineColour = $this->hexToAssColor($style['outline_color']);
        $alignment = $this->positionToAlignment($style['position']);
        $fontName = str_replace(["\r", "\n", ',', '\\'], ['', '', ' ', '\\\\'], $style['font']);
        [$bold, $italic] = $this->fontWeightToBoldItalic($style['font_weight'] ?? 'regular');

        $segmentDuration = $durationSeconds / count($chunks);
        // Center: marginV 0. Top/Bottom: use position_offset (pixels from edge).
        $marginV = $alignment === 5 ? 0 : (int) ($style['position_offset'] ?? 30);

        $ass = "[Script Info]\n";
        $ass .= "ScriptType: v4.00+\n";
        $ass .= "PlayResX: " . max(1, $playResX) . "\n";
        $ass .= "PlayResY: " . max(1, $playResY) . "\n";
        $ass .= "LayoutResX: " . max(1, $playResX) . "\n";
        $ass .= "LayoutResY: " . max(1, $playResY) . "\n\n";
        $ass .= "[V4+ Styles]\n";
        $ass .= "Format: Name, Fontname, Fontsize, PrimaryColour, SecondaryColour, OutlineColour, BackColour, Bold, Italic, Underline, StrikeOut, ScaleX, ScaleY, Spacing, Angle, BorderStyle, Outline, Shadow, Alignment, MarginL, MarginR, MarginV, Encoding\n";
        $ass .= sprintf(
            "Style: Default,%s,%d,%s,&H000000FF,%s,&H80000000,%d,%d,0,0,100,100,0,0,1,%d,0,%d,10,10,%d,1\n\n",
            $fontName,
            $style['font_size'],
            $primaryColour,
            $outlineColour,
            $bold,
            $italic,
            $style['outline_size'],
            $alignment,
            $marginV
        );
        $ass .= "[Events]\n";
        $ass .= "Format: Layer, Start, End, Style, Name, MarginL, MarginR, MarginV, Effect, Text\n";

        for ($i = 0; $i < count($chunks); $i++) {
            $start = $i * $segmentDuration;
            $end = min(($i + 1) * $segmentDuration, $durationSeconds);
            $startStr = $this->secondsToAssTime($start);
            $endStr = $this->secondsToAssTime($end);
            $text = str_replace(["\r", "\n"], ['', '\\N'], $chunks[$i]);
            $ass .= sprintf("Dialogue: 0,%s,%s,Default,,0,0,%d,,%s\n", $startStr, $endStr, $marginV, $text);
        }

        return $ass;
    }

    private function secondsToAssTime(float $seconds): string
    {
        $h = (int) floor($seconds / 3600);
        $m = (int) floor(($seconds % 3600) / 60);
        $s = $seconds - $h * 3600 - $m * 60;
        return sprintf('%d:%02d:%05.2f', $h, $m, $s);
    }

    private function resolveStyle(CaptionTemplate|array|null $templateOrConfig): array
    {
        if ($templateOrConfig instanceof CaptionTemplate) {
            return [
                'font' => $templateOrConfig->font ?: 'Arial',
                'font_size' => (int) ($templateOrConfig->font_size ?: 32),
                'font_weight' => $templateOrConfig->font_weight ?: 'regular',
                'font_color' => $templateOrConfig->font_color ?: '#FFFFFF',
                'outline_color' => $templateOrConfig->outline_color ?: '#000000',
                'outline_size' => (int) ($templateOrConfig->outline_size ?: 3),
                'position' => in_array($templateOrConfig->position, ['top', 'center', 'bottom']) ? $templateOrConfig->position : 'bottom',
                'position_offset' => (int) ($templateOrConfig->position_offset ?? 30),
            ];
        }
        if (is_array($templateOrConfig) && !empty($templateOrConfig)) {
            return [
                'font' => $templateOrConfig['font'] ?? 'Arial',
                'font_size' => (int) ($templateOrConfig['font_size'] ?? 32),
                'font_weight' => $templateOrConfig['font_weight'] ?? 'regular',
                'font_color' => $templateOrConfig['font_color'] ?? '#FFFFFF',
                'outline_color' => $templateOrConfig['outline_color'] ?? '#000000',
                'outline_size' => (int) ($templateOrConfig['outline_size'] ?? 3),
                'position' => in_array($templateOrConfig['position'] ?? '', ['top', 'center', 'bottom']) ? $templateOrConfig['position'] : 'bottom',
                'position_offset' => (int) ($templateOrConfig['position_offset'] ?? 30),
            ];
        }
        return $this->defaultStyle();
    }

    private function resolveWordsPerCaption(CaptionTemplate|array|null $templateOrConfig): int
    {
        if ($templateOrConfig instanceof CaptionTemplate) {
            return max(1, (int) ($templateOrConfig->words_per_caption ?? 3));
        }
        if (is_array($templateOrConfig)) {
            // Check both snake_case and camelCase variants
            $wordsPerCaption = $templateOrConfig['words_per_caption'] 
                ?? $templateOrConfig['wordsPerCaption'] 
                ?? null;
            if ($wordsPerCaption !== null) {
                return max(1, (int) $wordsPerCaption);
            }
        }
        return 3;
    }

    /**
     * Burn captions onto video. Returns path to output file.
     * @param CaptionTemplate|array|null $templateOrConfig Template, config array, or null
     * @param int $videoWidth Video width (for correct ASS positioning)
     * @param int $videoHeight Video height (for correct ASS positioning)
     */
    public function burnCaptions(
        string $inputPath,
        string $outputPath,
        string $captionText,
        float $durationSeconds,
        CaptionTemplate|array|null $templateOrConfig = null,
        int $videoWidth = 1920,
        int $videoHeight = 1080
    ): string {
        $inputPath = $this->normalizePath($inputPath);
        $outputPath = $this->normalizePath($outputPath);
        $dir = dirname($outputPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $assContent = $this->generateAssFile($captionText, $durationSeconds, $templateOrConfig, $videoWidth, $videoHeight);
        if ($assContent === '') {
            copy($inputPath, $outputPath);
            return $outputPath;
        }

        $assPath = $dir . DIRECTORY_SEPARATOR . 'caption_' . uniqid() . '.ass';
        file_put_contents($assPath, $assContent);

        try {
            $bin = config('ffmpeg.ffmpeg.binaries', 'ffmpeg');
            $assForFilter = str_replace('\\', '/', $assPath);
            $assForFilter = str_replace("'", "'\\''", $assForFilter);
            if (DIRECTORY_SEPARATOR === '\\' && preg_match('#^([A-Za-z]):#', $assForFilter, $m)) {
                $assForFilter = $m[1] . '\\:' . substr($assForFilter, 2);
            }
            $filter = "subtitles='{$assForFilter}'";

            $proc = new Process([
                $bin, '-i', $inputPath, '-vf', $filter, '-c:a', 'copy', '-y', $outputPath,
            ]);
            $proc->setTimeout((int) config('ffmpeg.timeout', 300));
            $proc->run();

            if (!$proc->isSuccessful()) {
                throw new \RuntimeException('FFmpeg caption burn failed: ' . $proc->getErrorOutput());
            }

            return $outputPath;
        } finally {
            if (file_exists($assPath)) {
                @unlink($assPath);
            }
        }
    }

    private function normalizePath(string $path): string
    {
        return DIRECTORY_SEPARATOR === '\\' ? str_replace('/', '\\', $path) : str_replace('\\', '/', $path);
    }
}
