<?php

declare(strict_types=1);

namespace Modules\StorageManagement\Http\Controllers\Api\V1\Customer;

use Modules\Core\Http\Controllers\Api\BaseApiController;
use Modules\StorageManagement\Actions\UploadFileAction;
use Modules\StorageManagement\Http\Requests\BulkFileUploadRequest;
use Modules\StorageManagement\Http\Requests\FileUploadRequest;
use Modules\StorageManagement\Models\StorageFile;
use Illuminate\Http\JsonResponse;

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
}
