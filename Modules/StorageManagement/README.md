# Storage Management Module

## Overview

The Storage Management module provides a unified interface for managing file storage across multiple providers. It supports local storage and S3-compatible services (DigitalOcean Spaces, AWS S3, Contabo Object Storage).

## Features

### Core Features

1. **Multiple Storage Drivers**
   - Local Storage
   - DigitalOcean Spaces (S3-compatible)
   - AWS S3
   - Contabo Object Storage (S3-compatible)

2. **Storage Configuration**
   - Configure multiple storage providers
   - Only one storage can be active at a time
   - Test connections before saving
   - Secure credential storage

3. **Storage Migration**
   - Migrate files from one storage to another
   - No data loss during migration
   - Progress tracking

4. **File Upload Management**
   - Single file uploads
   - Bulk file uploads
   - Queue-based uploads for large files
   - Upload progress tracking

5. **Storage Analytics**
   - Total storage usage
   - File count
   - Per-driver statistics

6. **File Cleanup**
   - Remove unused files
   - Configurable retention period
   - Safe deletion with error handling

## Installation

### Dependencies

The module requires the AWS SDK for S3 operations:

```bash
composer require aws/aws-sdk-php
```

### Database Migrations

Run the migrations:

```bash
php artisan migrate
```

## Usage

### Backend Usage

#### Using the File Upload Service

```php
use Modules\StorageManagement\Services\FileUploadService;

// Inject the service
public function __construct(
    private FileUploadService $uploadService
) {}

// Upload a single file
$storageFile = $this->uploadService->upload($uploadedFile, 'path/to/file');

// Bulk upload
$result = $this->uploadService->bulkUpload([$file1, $file2, $file3]);

// Queue upload for large files
$queueItem = $this->uploadService->queueUpload($largeFile);
```

#### Using Storage Management Service

```php
use Modules\StorageManagement\Services\StorageManagementService;

// Get current storage config
$config = $storageService->getCurrentConfig();

// Get storage usage
$usage = $storageService->getUsage();

// Migrate storage
$result = $storageService->migrateStorage($sourceId, $destinationId);

// Clean unused files
$result = $storageService->cleanUnusedFiles(30); // 30 days
```

#### Direct Storage Driver Usage

```php
use Modules\StorageManagement\Factories\StorageDriverFactory;

$driver = StorageDriverFactory::make();

// Upload file
$result = $driver->upload($file, 'path/to/file');

// Get file URL
$url = $driver->url('path/to/file');

// Delete file
$driver->delete('path/to/file');
```

### Frontend Usage

#### Upload Files

```typescript
import { storageManagementService } from '@/lib/api/services/storage-management.service'

// Single upload
const response = await storageManagementService.uploadFile(file, 'optional/path')

// Bulk upload
const response = await storageManagementService.bulkUploadFiles([file1, file2, file3])
```

#### Manage Storage Configuration

```typescript
// Get current config
const config = await storageManagementService.getCurrentConfig()

// Create new config
await storageManagementService.createConfig({
  driver: 'do_s3',
  key: 'access-key',
  secret: 'secret-key',
  region: 'nyc3',
  bucket: 'my-bucket',
})

// Test connection
await storageManagementService.testConnection(config)

// Get usage
const usage = await storageManagementService.getUsage()
```

## API Endpoints

### Admin Endpoints

- `GET /api/v1/admin/storage/config` - Get current storage configuration
- `GET /api/v1/admin/storage/configs` - List all storage configurations
- `POST /api/v1/admin/storage/config` - Create new storage configuration
- `PUT /api/v1/admin/storage/config/{id}` - Update storage configuration
- `POST /api/v1/admin/storage/config/test` - Test storage connection
- `GET /api/v1/admin/storage/usage` - Get storage usage statistics
- `POST /api/v1/admin/storage/migrate` - Migrate storage
- `POST /api/v1/admin/storage/cleanup` - Clean unused files
- `POST /api/v1/admin/storage/config/{id}/activate` - Activate storage configuration

### Customer Endpoints

- `POST /api/v1/customer/storage/upload` - Upload a single file
- `POST /api/v1/customer/storage/bulk-upload` - Bulk upload files

## Configuration

### Module Configuration

Edit `Modules/StorageManagement/config/module.php`:

```php
return [
    'supported_drivers' => ['local', 'do_s3', 'aws_s3', 'contabo_s3', 'cloudflare_r2', 'backblaze_b2'],
    'default_driver' => env('STORAGE_DRIVER', 'local'),
    'upload' => [
        'max_file_size' => 102400, // KB
        'allowed_mime_types' => 'image/*,video/*,audio/*',
        'queue_name' => 'default',
    ],
];
```

### Environment Variables

```env
STORAGE_DRIVER=local
MAX_UPLOAD_SIZE=102400
ALLOWED_MIME_TYPES=image/*,video/*,audio/*,application/pdf
```

## Storage Drivers

### Local Storage

No additional configuration required. Files are stored in Laravel's storage directory.

### DigitalOcean Spaces

Requires:
- Access Key ID
- Secret Access Key
- Region (e.g., `nyc3`)
- Bucket name
- Optional: Custom endpoint
- Optional: CDN URL

### AWS S3

Requires:
- Access Key ID
- Secret Access Key
- Region (e.g., `us-east-1`)
- Bucket name
- Optional: CDN URL

### Contabo Object Storage

Requires:
- Access Key ID
- Secret Access Key
- Region
- Bucket name
- Optional: Custom endpoint
- Optional: CDN URL

## Database Schema

### storage_settings

Stores storage configuration.

### storage_files

Tracks all uploaded files with metadata.

### storage_upload_queue

Manages queued file uploads.

## Security

- Secrets are never exposed in API responses
- File uploads are validated
- Only admins can configure storage
- Customers can only upload files

## Future Enhancements

- User file upload limits
- Per-user storage usage tracking
- Automatic file compression
- CDN integration
- File versioning
