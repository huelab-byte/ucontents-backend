<?php

declare(strict_types=1);

namespace Modules\StorageManagement\Drivers;

use Modules\StorageManagement\Contracts\StorageDriverInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;

class LocalStorageDriver implements StorageDriverInterface
{
    protected string $disk;

    public function __construct(array $config = [])
    {
        // Use 'public' disk by default for publicly accessible files
        // 'local' disk is for private files only
        $this->disk = $config['disk'] ?? 'public';
    }

    public function upload($file, string $path, array $options = []): array
    {
        if ($file instanceof UploadedFile) {
            $storedPath = Storage::disk($this->disk)->putFile($path, $file, $options);
        } else {
            // If it's a file path, copy it
            $storedPath = $path . '/' . basename($file);
            Storage::disk($this->disk)->put($storedPath, File::get($file));
        }

        return [
            'path' => $storedPath,
            'url' => Storage::disk($this->disk)->url($storedPath),
        ];
    }

    public function delete(string $path): bool
    {
        return Storage::disk($this->disk)->delete($path);
    }

    public function exists(string $path): bool
    {
        return Storage::disk($this->disk)->exists($path);
    }

    public function url(string $path): ?string
    {
        try {
            return Storage::disk($this->disk)->url($path);
        } catch (\Exception $e) {
            return null;
        }
    }

    public function size(string $path): int
    {
        return (int) Storage::disk($this->disk)->size($path);
    }

    public function testConnection(): bool
    {
        try {
            $testPath = 'test_' . time() . '.txt';
            Storage::disk($this->disk)->put($testPath, 'test');
            Storage::disk($this->disk)->delete($testPath);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function getUsage(): array
    {
        $files = Storage::disk($this->disk)->allFiles();
        $totalSize = 0;
        
        foreach ($files as $file) {
            $totalSize += Storage::disk($this->disk)->size($file);
        }

        return [
            'total_size' => $totalSize,
            'file_count' => count($files),
        ];
    }

    public function listFiles(string $path = '', bool $recursive = false): array
    {
        if ($recursive) {
            return Storage::disk($this->disk)->allFiles($path);
        }
        return Storage::disk($this->disk)->files($path);
    }

    public function copy(string $sourcePath, string $destinationPath): bool
    {
        try {
            $content = Storage::disk($this->disk)->get($sourcePath);
            return Storage::disk($this->disk)->put($destinationPath, $content) !== false;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function getLocalPath(string $path): string
    {
        $fullPath = Storage::disk($this->disk)->path($path);
        
        // Normalize path separators for cross-platform compatibility
        if (DIRECTORY_SEPARATOR === '\\') {
            return str_replace('/', '\\', $fullPath);
        }
        return str_replace('\\', '/', $fullPath);
    }

    public function cleanupLocalPath(string $localPath, string $originalPath): void
    {
        // For local storage, no cleanup needed as we return the actual path
    }
}
