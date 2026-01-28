<?php

declare(strict_types=1);

namespace Modules\BgmLibrary\Services;

use Illuminate\Support\Facades\Log;

/**
 * Service for processing BGM files and extracting properties
 */
class BgmProcessingService
{
    /**
     * Get BGM properties using ffprobe
     */
    public function getBgmProperties(string $bgmPath): array
    {
        try {
            // Use ffprobe to get audio information
            $command = sprintf(
                'ffprobe -v quiet -print_format json -show_format -show_streams "%s"',
                $bgmPath
            );
            
            $output = shell_exec($command);
            
            if (!$output) {
                Log::warning('ffprobe returned no output', ['path' => $bgmPath]);
                return $this->getDefaultProperties($bgmPath);
            }
            
            $data = json_decode($output, true);
            
            if (!$data) {
                Log::warning('Failed to parse ffprobe output', ['path' => $bgmPath]);
                return $this->getDefaultProperties($bgmPath);
            }
            
            // Find audio stream
            $audioStream = null;
            foreach ($data['streams'] ?? [] as $stream) {
                if ($stream['codec_type'] === 'audio') {
                    $audioStream = $stream;
                    break;
                }
            }
            
            $format = $data['format'] ?? [];
            
            return [
                'duration' => (float) ($format['duration'] ?? 0),
                'bitrate' => (int) ($format['bit_rate'] ?? 0),
                'sample_rate' => (int) ($audioStream['sample_rate'] ?? 0),
                'channels' => (int) ($audioStream['channels'] ?? 0),
                'format' => pathinfo($bgmPath, PATHINFO_EXTENSION),
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get BGM properties', [
                'path' => $bgmPath,
                'error' => $e->getMessage(),
            ]);
            return $this->getDefaultProperties($bgmPath);
        }
    }
    
    /**
     * Get default properties when ffprobe fails
     */
    private function getDefaultProperties(string $bgmPath): array
    {
        return [
            'duration' => 0,
            'bitrate' => 0,
            'sample_rate' => 0,
            'channels' => 0,
            'format' => pathinfo($bgmPath, PATHINFO_EXTENSION),
        ];
    }
}
