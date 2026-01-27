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

    public function upload($file, string $path, array $options = []): array
    {
        try {
            if ($file instanceof UploadedFile) {
                $content = file_get_contents($file->getRealPath());
            } else {
                $content = file_get_contents($file);
            }

            $this->client->putObject([
                'Bucket' => $this->bucket,
                'Key' => $path,
                'Body' => $content,
                'ContentType' => $options['content_type'] ?? ($file instanceof UploadedFile ? $file->getMimeType() : null),
                'ACL' => $options['acl'] ?? 'private',
            ]);

            return [
                'path' => $path,
                'url' => $this->url($path),
            ];
        } catch (AwsException $e) {
            throw new \RuntimeException('Failed to upload file: ' . $e->getMessage());
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
            $testPath = 'test_' . time() . '.txt';
            $this->client->putObject([
                'Bucket' => $this->bucket,
                'Key' => $testPath,
                'Body' => 'test',
            ]);
            $this->client->deleteObject([
                'Bucket' => $this->bucket,
                'Key' => $testPath,
            ]);
            return true;
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
            $tempDir = storage_path('app/temp/s3-downloads');
            if (!is_dir($tempDir)) {
                mkdir($tempDir, 0755, true);
            }

            $extension = pathinfo($path, PATHINFO_EXTENSION);
            $tempPath = $tempDir . '/' . uniqid('s3_') . '.' . $extension;

            $result = $this->client->getObject([
                'Bucket' => $this->bucket,
                'Key' => $path,
            ]);

            file_put_contents($tempPath, $result['Body']);

            return $tempPath;
        } catch (AwsException $e) {
            throw new \RuntimeException('Failed to download file for local processing: ' . $e->getMessage());
        }
    }

    public function cleanupLocalPath(string $localPath, string $originalPath): void
    {
        // For S3, we need to delete the temporary downloaded file
        $tempDir = storage_path('app/temp/s3-downloads');
        if (str_starts_with($localPath, $tempDir) && file_exists($localPath)) {
            unlink($localPath);
        }
    }
}
