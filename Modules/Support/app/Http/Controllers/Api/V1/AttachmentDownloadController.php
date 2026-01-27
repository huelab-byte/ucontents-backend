<?php

declare(strict_types=1);

namespace Modules\Support\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Modules\Core\Http\Controllers\Api\BaseApiController;
use Modules\StorageManagement\Models\StorageFile;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttachmentDownloadController extends BaseApiController
{
    /**
     * Download an attachment file
     */
    public function download(int $id): StreamedResponse|JsonResponse
    {
        $file = StorageFile::find($id);

        if (!$file) {
            return $this->notFound('File not found');
        }

        // Get the disk and path
        $disk = $file->disk ?? 'local';
        $path = $file->path;

        // Check if file exists in storage
        if (!Storage::disk($disk)->exists($path)) {
            // If not in storage, try to stream from URL
            if ($file->url) {
                return $this->streamFromUrl($file->url, $file->original_name, $file->mime_type);
            }

            return $this->notFound('File not found in storage');
        }

        // Stream the file from storage with download headers
        return Storage::disk($disk)->download(
            $path,
            $file->original_name,
            [
                'Content-Type' => $file->mime_type,
                'Content-Disposition' => 'attachment; filename="' . $file->original_name . '"',
            ]
        );
    }

    /**
     * Stream a file from a URL for download
     */
    private function streamFromUrl(string $url, string $filename, ?string $mimeType = null): StreamedResponse
    {
        return response()->stream(function () use ($url) {
            $handle = fopen($url, 'rb');
            if ($handle) {
                while (!feof($handle)) {
                    echo fread($handle, 8192);
                    flush();
                }
                fclose($handle);
            }
        }, 200, [
            'Content-Type' => $mimeType ?? 'application/octet-stream',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }
}
