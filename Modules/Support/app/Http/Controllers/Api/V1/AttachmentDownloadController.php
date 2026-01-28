<?php

declare(strict_types=1);

namespace Modules\Support\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Modules\Core\Http\Controllers\Api\BaseApiController;
use Modules\StorageManagement\Models\StorageFile;
use Modules\Support\Models\SupportTicketAttachment;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AttachmentDownloadController extends BaseApiController
{
    /**
     * Download an attachment file
     * Uses StorageManagement module to handle files from any storage provider
     */
    public function download(int $id): StreamedResponse|BinaryFileResponse|JsonResponse
    {
        $file = StorageFile::find($id);

        if (!$file) {
            return $this->notFound('File not found');
        }

        // Security check: verify file is attached to a support ticket
        $attachment = SupportTicketAttachment::where('storage_file_id', $id)->first();
        if (!$attachment) {
            return $this->error('File is not a valid support attachment', 403);
        }

        // Additional security: check user can access this ticket
        $user = auth()->user();
        $ticket = $attachment->supportTicket ?? $attachment->supportTicketReply?->supportTicket;
        
        if ($ticket && !$user->hasAnyRole(['super_admin', 'admin'])) {
            // Customer can only download files from their own tickets
            if ($ticket->user_id !== $user->id) {
                return $this->error('Unauthorized access to file', 403);
            }
        }

        // Mark file as accessed
        $file->markAsAccessed();

        // Try to download using the best available method
        return $this->downloadFile($file);
    }

    /**
     * Download file using the best available method
     */
    private function downloadFile(StorageFile $file): StreamedResponse|BinaryFileResponse|JsonResponse
    {
        try {
            // Method 1: For local storage, use direct file response (most efficient)
            if ($file->driver === 'local') {
                $localPath = $file->getLocalPath();
                if (file_exists($localPath)) {
                    return response()->download($localPath, $file->original_name, [
                        'Content-Type' => $file->mime_type ?? 'application/octet-stream',
                    ]);
                }
            }

            // Method 2: Try streaming from storage
            $content = $file->getContent();
            if ($content !== null) {
                return response()->streamDownload(function () use ($content) {
                    echo $content;
                }, $file->original_name, [
                    'Content-Type' => $file->mime_type ?? 'application/octet-stream',
                    'Content-Length' => $file->size,
                ]);
            }

            // Method 3: Fallback to URL redirect for remote storage
            if ($file->url) {
                return $this->streamFromUrl($file->url, $file->original_name, $file->mime_type);
            }

            Log::error('Failed to download file - no available method', [
                'storage_file_id' => $file->id,
                'driver' => $file->driver,
                'path' => $file->path,
            ]);

            return $this->error('Unable to download file', 500);
            
        } catch (\Exception $e) {
            Log::error('File download failed', [
                'storage_file_id' => $file->id,
                'error' => $e->getMessage(),
            ]);

            // Last resort: try URL if available
            if ($file->url) {
                return $this->streamFromUrl($file->url, $file->original_name, $file->mime_type);
            }

            return $this->error('File download failed: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Stream a file from a URL for download (fallback for external URLs)
     */
    private function streamFromUrl(string $url, string $filename, ?string $mimeType = null): StreamedResponse
    {
        return response()->stream(function () use ($url) {
            $context = stream_context_create([
                'http' => [
                    'timeout' => 60,
                    'follow_location' => true,
                ],
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                ],
            ]);
            
            $handle = @fopen($url, 'rb', false, $context);
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
