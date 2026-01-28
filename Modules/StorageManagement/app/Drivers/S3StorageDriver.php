<?php

declare(strict_types=1);

namespace Modules\StorageManagement\Drivers;

use Modules\StorageManagement\Contracts\StorageDriverInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Aws\S3\S3Client;
use Aws\Exception\AwsException;

abstract class S3StorageDriver implements StorageDriverInterface
{
    protected S3Client $client;
    protected string $bucket;
    protected string $region;
    protected ?string $endpoint;
    protected bool $usePathStyleEndpoint;
    protected ?string $url;

    public function __construct(array $config)
    {
        $this->bucket = $config['bucket'] ?? '';
        $this->region = $config['region'] ?? 'us-east-1';
        $this->endpoint = $config['endpoint'] ?? null;
        $this->usePathStyleEndpoint = $config['use_path_style_endpoint'] ?? false;
        $this->url = $config['url'] ?? null;

        $this->client = new S3Client([
            'version' => 'latest',
            'region' => $this->region,
            'credentials' => [
                'key' => $config['key'] ?? '',
                'secret' => $config['secret'] ?? '',
            ],
            'endpoint' => $this->endpoint,
            'use_path_style_endpoint' => $this->usePathStyleEndpoint,
        ]);
    }

    /**
     * Check if this is a Cloudflare R2 endpoint
     * R2 endpoints contain 'r2.cloudflarestorage.com'
     */
    protected function isCloudflareR2(): bool
    {
        return $this->endpoint && str_contains($this->endpoint, 'r2.cloudflarestorage.com');
    }

    public function upload($file, string $path, array $options = []): array
    {
        try {
            if ($file instanceof UploadedFile) {
                $content = file_get_contents($file->getRealPath());
                $extension = $file->getClientOriginalExtension();
                $mimeType = $file->getMimeType();
            } else {
                $content = file_get_contents($file);
                $extension = pathinfo($file, PATHINFO_EXTENSION);
                $mimeType = null;
            }

            // Generate unique filename and build full path
            // Path is treated as directory, we generate the filename
            $uniqueFilename = bin2hex(random_bytes(20)) . ($extension ? '.' . $extension : '');
            $fullPath = rtrim($path, '/') . '/' . $uniqueFilename;

            $putParams = [
                'Bucket' => $this->bucket,
                'Key' => $fullPath,
                'Body' => $content,
                'ContentType' => $options['content_type'] ?? $mimeType,
            ];
            
            // Only set ACL if not using Cloudflare R2 (R2 doesn't support S3 ACLs)
            // R2 uses bucket-level access settings instead
            if (!$this->isCloudflareR2()) {
                $putParams['ACL'] = $options['acl'] ?? 'public-read';
            }
            
            $this->client->putObject($putParams);

            return [
                'path' => $fullPath,
                'url' => $this->url($fullPath),
            ];
        } catch (AwsException $e) {
            throw new \RuntimeException('Failed to upload file: ' . $e->getMessage());
        }
    }

    /**
     * Upload content directly with exact path (used for migration)
     * Unlike upload(), this preserves the exact path without generating a new filename
     */
    public function putObjectDirect(string $path, string $content, ?string $mimeType = null): array
    {
        try {
            $putParams = [
                'Bucket' => $this->bucket,
                'Key' => $path,
                'Body' => $content,
            ];
            
            if ($mimeType) {
                $putParams['ContentType'] = $mimeType;
            }
            
            // Only set ACL if not using Cloudflare R2
            if (!$this->isCloudflareR2()) {
                $putParams['ACL'] = 'public-read';
            }
            
            $this->client->putObject($putParams);

            return [
                'path' => $path,
                'url' => $this->url($path),
            ];
        } catch (AwsException $e) {
            throw new \RuntimeException('Failed to upload file directly: ' . $e->getMessage());
        }
    }

    public function delete(string $path): bool
    {
        try {
            $this->client->deleteObject([
                'Bucket' => $this->bucket,
                'Key' => $path,
            ]);
            return true;
        } catch (AwsException $e) {
            return false;
        }
    }

    public function exists(string $path): bool
    {
        try {
            return $this->client->doesObjectExist($this->bucket, $path);
        } catch (AwsException $e) {
            return false;
        }
    }

    public function url(string $path): ?string
    {
        if ($this->url) {
            return rtrim($this->url, '/') . '/' . ltrim($path, '/');
        }

        try {
            return $this->client->getObjectUrl($this->bucket, $path);
        } catch (AwsException $e) {
            return null;
        }
    }

    public function size(string $path): int
    {
        try {
            $result = $this->client->headObject([
                'Bucket' => $this->bucket,
                'Key' => $path,
            ]);
            return (int) $result['ContentLength'];
        } catch (AwsException $e) {
            return 0;
        }
    }

    public function testConnection(): bool
    {
        try {
            // Use a dedicated test folder and unique filename to avoid clutter
            $testPath = '.storage-test/' . uniqid('test_', true) . '.txt';
            
            $this->client->putObject([
                'Bucket' => $this->bucket,
                'Key' => $testPath,
                'Body' => 'connection test - ' . date('Y-m-d H:i:s'),
            ]);
            
            // Verify the file exists
            $exists = $this->client->doesObjectExist($this->bucket, $testPath);
            
            // Always try to delete the test file
            try {
                $this->client->deleteObject([
                    'Bucket' => $this->bucket,
                    'Key' => $testPath,
                ]);
            } catch (AwsException $e) {
                // Ignore delete errors - file will be in .storage-test folder
            }
            
            return $exists;
        } catch (AwsException $e) {
            return false;
        }
    }

    public function getUsage(): array
    {
        try {
            $totalSize = 0;
            $fileCount = 0;
            $paginator = $this->client->getPaginator('ListObjects', [
                'Bucket' => $this->bucket,
            ]);

            foreach ($paginator as $result) {
                if (isset($result['Contents'])) {
                    foreach ($result['Contents'] as $object) {
                        $totalSize += (int) $object['Size'];
                        $fileCount++;
                    }
                }
            }

            return [
                'total_size' => $totalSize,
                'file_count' => $fileCount,
            ];
        } catch (AwsException $e) {
            return [
                'total_size' => 0,
                'file_count' => 0,
            ];
        }
    }

    public function listFiles(string $path = '', bool $recursive = false): array
    {
        try {
            $files = [];
            $params = [
                'Bucket' => $this->bucket,
                'Prefix' => $path,
            ];

            if (!$recursive) {
                $params['Delimiter'] = '/';
            }

            $paginator = $this->client->getPaginator('ListObjects', $params);

            foreach ($paginator as $result) {
                if (isset($result['Contents'])) {
                    foreach ($result['Contents'] as $object) {
                        $files[] = $object['Key'];
                    }
                }
            }

            return $files;
        } catch (AwsException $e) {
            return [];
        }
    }

    public function copy(string $sourcePath, string $destinationPath): bool
    {
        try {
            $this->client->copyObject([
                'Bucket' => $this->bucket,
                'CopySource' => $this->bucket . '/' . $sourcePath,
                'Key' => $destinationPath,
            ]);
            return true;
        } catch (AwsException $e) {
            return false;
        }
    }

    public function getLocalPath(string $path): string
    {
        try {
            // Download file to temporary location for local processing
            // Use Laravel's storage path, fallback to system temp if not writable
            $tempDir = storage_path('app' . DIRECTORY_SEPARATOR . 'temp' . DIRECTORY_SEPARATOR . 's3-downloads');
            
            // Normalize path separators for Windows
            $tempDir = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $tempDir);
            
            if (!is_dir($tempDir)) {
                if (!@mkdir($tempDir, 0755, true) && !is_dir($tempDir)) {
                    // Fallback to system temp directory if storage path fails
                    $tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 's3-downloads';
                    if (!is_dir($tempDir)) {
                        mkdir($tempDir, 0755, true);
                    }
                }
            }

            // Get extension from path, default to 'tmp' if none found
            $extension = pathinfo($path, PATHINFO_EXTENSION);
            if (empty($extension)) {
                // Try to get extension from the full path (might be in folders)
                $basename = basename($path);
                $extension = pathinfo($basename, PATHINFO_EXTENSION);
            }
            $extension = !empty($extension) ? $extension : 'tmp';
            
            $tempPath = $tempDir . DIRECTORY_SEPARATOR . uniqid('s3_') . '.' . $extension;

            $result = $this->client->getObject([
                'Bucket' => $this->bucket,
                'Key' => $path,
            ]);

            // Write to temp file
            $written = @file_put_contents($tempPath, $result['Body']);
            if ($written === false) {
                throw new \RuntimeException('Failed to write temp file. Check directory permissions: ' . $tempDir);
            }

            return $tempPath;
        } catch (AwsException $e) {
            throw new \RuntimeException('Failed to download file for local processing: ' . $e->getMessage());
        }
    }

    public function cleanupLocalPath(string $localPath, string $originalPath): void
    {
        // For S3, we need to delete the temporary downloaded file
        // Check both possible temp directories
        $storageTempDir = storage_path('app' . DIRECTORY_SEPARATOR . 'temp' . DIRECTORY_SEPARATOR . 's3-downloads');
        $systemTempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 's3-downloads';
        
        // Normalize paths for comparison
        $localPath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $localPath);
        $storageTempDir = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $storageTempDir);
        $systemTempDir = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $systemTempDir);
        
        if ((str_starts_with($localPath, $storageTempDir) || str_starts_with($localPath, $systemTempDir)) 
            && file_exists($localPath)) {
            @unlink($localPath);
        }
    }

    public function deleteDirectory(string $path): bool
    {
        try {
            // S3 doesn't have real directories - they're just prefixes
            // We need to delete all objects with this prefix
            $prefix = rtrim($path, '/') . '/';
            
            // List all objects with this prefix
            $objects = [];
            $paginator = $this->client->getPaginator('ListObjectsV2', [
                'Bucket' => $this->bucket,
                'Prefix' => $prefix,
            ]);

            foreach ($paginator as $result) {
                if (isset($result['Contents'])) {
                    foreach ($result['Contents'] as $object) {
                        $objects[] = ['Key' => $object['Key']];
                    }
                }
            }

            // If no objects found, directory doesn't exist (which is fine)
            // For S3, empty directories don't exist, so this is correct
            if (empty($objects)) {
                return true;
            }

            // Delete objects in batches of 1000 (S3 limit)
            foreach (array_chunk($objects, 1000) as $batch) {
                $this->client->deleteObjects([
                    'Bucket' => $this->bucket,
                    'Delete' => [
                        'Objects' => $batch,
                        'Quiet' => true,
                    ],
                ]);
            }

            // Verify deletion was successful by checking if any objects remain
            // This ensures the directory (prefix) is truly empty
            $remainingObjects = [];
            $verifyPaginator = $this->client->getPaginator('ListObjectsV2', [
                'Bucket' => $this->bucket,
                'Prefix' => $prefix,
            ]);

            foreach ($verifyPaginator as $result) {
                if (isset($result['Contents'])) {
                    foreach ($result['Contents'] as $object) {
                        $remainingObjects[] = $object['Key'];
                    }
                }
            }

            // If objects remain, deletion was not fully successful
            if (!empty($remainingObjects)) {
                return false;
            }

            return true;
        } catch (AwsException $e) {
            return false;
        }
    }

    public function deleteMultiple(array $paths): int
    {
        if (empty($paths)) {
            return 0;
        }

        try {
            $objects = array_map(fn($path) => ['Key' => $path], $paths);
            
            // Delete in batches of 1000 (S3 limit)
            $deleted = 0;
            foreach (array_chunk($objects, 1000) as $batch) {
                $result = $this->client->deleteObjects([
                    'Bucket' => $this->bucket,
                    'Delete' => [
                        'Objects' => $batch,
                        'Quiet' => false,
                    ],
                ]);
                
                $deleted += count($result['Deleted'] ?? []);
            }
            
            return $deleted;
        } catch (AwsException $e) {
            return 0;
        }
    }

    public function getContent(string $path): ?string
    {
        try {
            $result = $this->client->getObject([
                'Bucket' => $this->bucket,
                'Key' => $path,
            ]);
            return (string) $result['Body'];
        } catch (AwsException $e) {
            return null;
        }
    }

    public function getStream(string $path)
    {
        try {
            $result = $this->client->getObject([
                'Bucket' => $this->bucket,
                'Key' => $path,
            ]);
            
            // Return the stream from the result body
            $body = $result['Body'];
            if ($body instanceof \Psr\Http\Message\StreamInterface) {
                return $body->detach();
            }
            return null;
        } catch (AwsException $e) {
            return null;
        }
    }
}
