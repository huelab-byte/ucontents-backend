<?php

declare(strict_types=1);

namespace Modules\AudioLibrary\Services;

use Illuminate\Support\Facades\Log;

/**
 * Service for processing audio files and extracting properties
 */
class AudioProcessingService
{
    /**
     * Get audio properties using ffprobe
     */
    public function getAudioProperties(string $audioPath): array
    {
        try {
            // Use ffprobe to get audio information
            $command = sprintf(
                'ffprobe -v quiet -print_format json -show_format -show_streams "%s"',
                $audioPath
            );
            
            $output = shell_exec($command);
            
            if (!$output) {
                Log::warning('ffprobe returned no output', ['path' => $audioPath]);
                return $this->getDefaultProperties($audioPath);
            }
            
            $data = json_decode($output, true);
            
            if (!$data) {
                Log::warning('Failed to parse ffprobe output', ['path' => $audioPath]);
                return $this->getDefaultProperties($audioPath);
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
                'format' => pathinfo($audioPath, PATHINFO_EXTENSION),
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get audio properties', [
                'path' => $audioPath,
                'error' => $e->getMessage(),
            ]);
            return $this->getDefaultProperties($audioPath);
        }
    }
    
    /**
     * Get default properties when ffprobe fails
     */
    private function getDefaultProperties(string $audioPath): array
    {
        return [
            'duration' => 0,
            'bitrate' => 0,
            'sample_rate' => 0,
            'channels' => 0,
            'format' => pathinfo($audioPath, PATHINFO_EXTENSION),
        ];
    }
}
