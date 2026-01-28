<?php

declare(strict_types=1);

namespace Modules\StorageManagement\Contracts;

use Illuminate\Http\UploadedFile;

interface StorageDriverInterface
{
    /**
     * Upload a file to storage
     *
     * @param UploadedFile|string $file File to upload or file path
     * @param string $path Destination path
     * @param array $options Additional options
     * @return array ['path' => string, 'url' => string|null]
     */
    public function upload($file, string $path, array $options = []): array;

    /**
     * Delete a file from storage
     *
     * @param string $path File path
     * @return bool
     */
    public function delete(string $path): bool;

    /**
     * Check if file exists
     *
     * @param string $path File path
     * @return bool
     */
    public function exists(string $path): bool;

    /**
     * Get file URL
     *
     * @param string $path File path
     * @return string|null
     */
    public function url(string $path): ?string;

    /**
     * Get file size
     *
     * @param string $path File path
     * @return int Bytes
     */
    public function size(string $path): int;

    /**
     * Test connection to storage
     *
     * @return bool
     */
    public function testConnection(): bool;

    /**
     * Get storage usage statistics
     *
     * @return array ['total_size' => int, 'file_count' => int]
     */
    public function getUsage(): array;

    /**
     * List files in a directory
     *
     * @param string $path Directory path
     * @param bool $recursive
     * @return array
     */
    public function listFiles(string $path = '', bool $recursive = false): array;

    /**
     * Copy file from one location to another (for migration)
     *
     * @param string $sourcePath Source path
     * @param string $destinationPath Destination path
     * @return bool
     */
    public function copy(string $sourcePath, string $destinationPath): bool;

    /**
     * Get local filesystem path for processing
     * For local storage, returns the actual path
     * For remote storage (S3), downloads to temp and returns temp path
     *
     * @param string $path File path in storage
     * @return string Local filesystem path
     */
    public function getLocalPath(string $path): string;

    /**
     * Cleanup temporary file if it was created
     *
     * @param string $localPath Path returned by getLocalPath
     * @param string $originalPath Original storage path
     * @return void
     */
    public function cleanupLocalPath(string $localPath, string $originalPath): void;

    /**
     * Upload content directly with exact path (used for migration)
     * Unlike upload(), this preserves the exact path without generating a new filename
     *
     * @param string $path Exact destination path
     * @param string $content File content
     * @param string|null $mimeType MIME type
     * @return array ['path' => string, 'url' => string|null]
     */
    public function putObjectDirect(string $path, string $content, ?string $mimeType = null): array;

    /**
     * Delete a directory and all its contents
     * For local storage: removes the directory
     * For S3: deletes all objects with the prefix
     *
     * @param string $path Directory path/prefix
     * @return bool
     */
    public function deleteDirectory(string $path): bool;

    /**
     * Delete multiple files at once
     *
     * @param array $paths Array of file paths
     * @return int Number of files successfully deleted
     */
    public function deleteMultiple(array $paths): int;

    /**
     * Get file content as string
     *
     * @param string $path File path
     * @return string|null File content or null if not found
     */
    public function getContent(string $path): ?string;

    /**
     * Get a stream resource for the file
     *
     * @param string $path File path
     * @return resource|null Stream resource or null if not found
     */
    public function getStream(string $path);
}
