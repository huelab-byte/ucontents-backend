<?php

declare(strict_types=1);

namespace Modules\MediaUpload\Actions;

use Modules\MediaUpload\Models\MediaUpload;
use Modules\StorageManagement\Factories\StorageDriverFactory;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class DeleteMediaUploadAction
{
    public function execute(MediaUpload $mediaUpload): bool
    {
        return DB::transaction(function () use ($mediaUpload) {
            $storageFile = $mediaUpload->storageFile;
            if ($storageFile) {
                try {
                    $driver = StorageDriverFactory::make($storageFile->driver);
                    if ($driver->exists($storageFile->path)) {
                        $driver->delete($storageFile->path);
                    }
                    $storageFile->forceDelete();
                } catch (\Exception $e) {
                    Log::error('Failed to delete media upload storage file', [
                        'media_upload_id' => $mediaUpload->id,
                        'error' => $e->getMessage(),
                    ]);
                    throw $e;
                }
            }
            $mediaUpload->delete();
            return true;
        });
    }
}
