<?php

declare(strict_types=1);

namespace Modules\StorageManagement\Http\Controllers\Api\V1\Customer;

use Modules\Core\Http\Controllers\Api\BaseApiController;
use Modules\StorageManagement\Actions\UploadFileAction;
use Modules\StorageManagement\Http\Requests\BulkFileUploadRequest;
use Modules\StorageManagement\Http\Requests\FileUploadRequest;
use Modules\StorageManagement\Models\StorageFile;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class FileUploadController extends BaseApiController
{
    public function __construct(
        private UploadFileAction $uploadAction
    ) {}

    /**
     * Upload a single file
     */
    public function upload(FileUploadRequest $request): JsonResponse
    {
        try {
            $file = $request->file('file');
            $path = $request->validated()['path'] ?? null;
            
            $storageFile = $this->uploadAction->execute($file, $path);
            
            return $this->created([
                'id' => $storageFile->id,
                'path' => $storageFile->path,
                'url' => $storageFile->url,
                'original_name' => $storageFile->original_name,
                'size' => $storageFile->size,
            ], 'File uploaded successfully');
        } catch (\Exception $e) {
            return $this->error('Upload failed: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Bulk upload files
     */
    public function bulkUpload(BulkFileUploadRequest $request): JsonResponse
    {
        try {
            $uploaded = [];
            $failed = [];

            foreach ($request->file('files') as $file) {
                try {
                    $storageFile = $this->uploadAction->execute($file);
                    $uploaded[] = [
                        'id' => $storageFile->id,
                        'path' => $storageFile->path,
                        'url' => $storageFile->url,
                        'original_name' => $storageFile->original_name,
                    ];
                } catch (\Exception $e) {
                    $failed[] = [
                        'original_name' => $file->getClientOriginalName(),
                        'error' => $e->getMessage(),
                    ];
                }
            }

            return $this->success([
                'uploaded' => $uploaded,
                'failed' => $failed,
                'total' => count($request->file('files')),
                'success_count' => count($uploaded),
                'failed_count' => count($failed),
            ], 'Bulk upload completed');
        } catch (\Exception $e) {
            return $this->error('Bulk upload failed: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Download a file by ID
     * User can only download their own files
     */
    public function download(int $id): StreamedResponse|BinaryFileResponse|JsonResponse
    {
        $file = StorageFile::find($id);

        if (!$file) {
            return $this->notFound('File not found');
        }

        // Security check: user can only download their own files
        $user = auth()->user();
        if (!$user->hasRole(['super_admin', 'admin']) && $file->user_id !== $user->id) {
            return $this->error('Unauthorized access to file', 403);
        }

        // Mark file as accessed
        $file->markAsAccessed();

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
                return response()->redirectTo($file->url);
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

            // Last resort: redirect to URL if available
            if ($file->url) {
                return response()->redirectTo($file->url);
            }

            return $this->error('File download failed: ' . $e->getMessage(), 500);
        }
    }
}
