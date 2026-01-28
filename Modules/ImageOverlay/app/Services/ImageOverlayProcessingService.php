<?php

declare(strict_types=1);

namespace Modules\ImageOverlay\Services;

use Illuminate\Support\Facades\Log;

/**
 * Service for extracting image overlay properties
 */
class ImageOverlayProcessingService
{
    /**
     * Get image overlay properties using getimagesize
     */
    public function getImageProperties(string $imagePath): array
    {
        try {
            $imageInfo = @getimagesize($imagePath);
            
            if ($imageInfo === false) {
                Log::warning('Failed to get image info', ['path' => $imagePath]);
                return $this->getDefaultProperties();
            }

            $fileSize = @filesize($imagePath);

            return [
                'width' => $imageInfo[0] ?? null,
                'height' => $imageInfo[1] ?? null,
                'format' => $this->getFormatFromMimeType($imageInfo['mime'] ?? null),
                'mime_type' => $imageInfo['mime'] ?? null,
                'file_size' => $fileSize ?: null,
            ];
        } catch (\Exception $e) {
            Log::error('Failed to extract image overlay properties', [
                'path' => $imagePath,
                'error' => $e->getMessage(),
            ]);
            return $this->getDefaultProperties();
        }
    }

    /**
     * Get format from MIME type
     */
    private function getFormatFromMimeType(?string $mimeType): ?string
    {
        if (!$mimeType) {
            return null;
        }

        // Only formats that support transparency
        $formats = [
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
        ];

        return $formats[$mimeType] ?? null;
    }

    /**
     * Get default properties when extraction fails
     */
    private function getDefaultProperties(): array
    {
        return [
            'width' => null,
            'height' => null,
            'format' => null,
            'mime_type' => null,
            'file_size' => null,
        ];
    }
}
