<?php

declare(strict_types=1);

namespace Modules\StorageManagement\Actions;

use Illuminate\Http\UploadedFile;
use Modules\StorageManagement\Factories\StorageDriverFactory;
use Modules\StorageManagement\Models\StorageFile;
use Illuminate\Support\Str;

class UploadFileAction
{
    public function execute(
        UploadedFile $file,
        ?string $path = null,
        ?int $userId = null,
        $reference = null
    ): StorageFile {
        $driver = StorageDriverFactory::make();
        
        // Path is treated as directory - each driver handles filename generation
        // If no path provided, use date-based directory structure
        $directory = $path ? rtrim($path, '/') : date('Y/m/d');

        // Upload file - driver will generate unique filename
        $result = $driver->upload($file, $directory);

        // Get active storage setting to determine driver
        $activeSetting = \Modules\StorageManagement\Models\StorageSetting::getActive();
        
        // Create database record
        return StorageFile::create([
            'driver' => $activeSetting?->driver ?? 'local',
            'path' => $result['path'],
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'url' => $result['url'],
            'user_id' => $userId ?? auth()->id(),
            'reference_type' => $reference ? get_class($reference) : null,
            'reference_id' => $reference?->id,
            'is_used' => true,
        ]);
    }
}
