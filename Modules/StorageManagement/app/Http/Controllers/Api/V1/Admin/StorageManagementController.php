<?php

declare(strict_types=1);

namespace Modules\StorageManagement\Http\Controllers\Api\V1\Admin;

use Modules\Core\Http\Controllers\Api\BaseApiController;
use Modules\StorageManagement\Actions\DeleteStorageSettingAction;
use Modules\StorageManagement\Services\StorageManagementService;
use Modules\StorageManagement\Http\Requests\CleanupStorageRequest;
use Modules\StorageManagement\Http\Requests\MigrateStorageRequest;
use Modules\StorageManagement\Http\Requests\StoreStorageConfigRequest;
use Modules\StorageManagement\Http\Requests\UpdateStorageConfigRequest;
use Modules\StorageManagement\Http\Resources\StorageSettingResource;
use Modules\StorageManagement\DTOs\StorageConfigDTO;
use Modules\StorageManagement\Models\StorageSetting;
use Illuminate\Http\JsonResponse;

class StorageManagementController extends BaseApiController
{
    public function __construct(
        private StorageManagementService $service,
        private DeleteStorageSettingAction $deleteStorageSettingAction
    ) {}

    /**
     * Get current storage configuration
     */
    public function index(): JsonResponse
    {
        $this->authorize('viewAny', StorageSetting::class);

        $config = $this->service->getCurrentConfig();
        
        if (!$config) {
            return $this->success(null, 'No storage configuration found');
        }

        return $this->success(new StorageSettingResource($config));
    }

    /**
     * Get all storage configurations
     */
    public function list(): JsonResponse
    {
        $this->authorize('viewAny', StorageSetting::class);

        $configs = StorageSetting::all();
        
        return $this->success(StorageSettingResource::collection($configs));
    }

    /**
     * Store new storage configuration
     */
    public function store(StoreStorageConfigRequest $request): JsonResponse
    {
        $this->authorize('create', StorageSetting::class);

        try {
            $dto = StorageConfigDTO::fromRequest($request);
            $config = $this->service->saveConfig($dto);
            
            return $this->created(new StorageSettingResource($config), 'Storage configuration created successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to create storage configuration: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Update storage configuration
     */
    public function update(UpdateStorageConfigRequest $request, int $id): JsonResponse
    {
        try {
            $setting = StorageSetting::findOrFail($id);
            $this->authorize('update', $setting);

            $dto = StorageConfigDTO::fromRequest($request);
            $config = $this->service->saveConfig($dto, $id);
            
            return $this->success(new StorageSettingResource($config), 'Storage configuration updated successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to update storage configuration: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Test storage connection
     */
    public function testConnection(StoreStorageConfigRequest $request): JsonResponse
    {
        $this->authorize('create', StorageSetting::class);

        try {
            $dto = StorageConfigDTO::fromRequest($request);
            $result = $this->service->testConnection($dto);
            
            if ($result) {
                return $this->success(['connected' => true], 'Connection successful');
            }
            
            return $this->error('Connection failed. Please check your credentials, region, bucket name, and endpoint.', 400);
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            
            // Provide helpful error messages based on common issues
            if (strpos($errorMessage, 'InvalidAccessKeyId') !== false || strpos($errorMessage, 'SignatureDoesNotMatch') !== false) {
                return $this->error('Invalid credentials. Please check your Access Key ID and Secret Access Key.', 400);
            }
            if (strpos($errorMessage, 'NoSuchBucket') !== false) {
                return $this->error('Bucket not found. Please verify the bucket name and region.', 400);
            }
            if (strpos($errorMessage, 'endpoint') !== false || strpos($errorMessage, 'host') !== false) {
                return $this->error('Endpoint configuration error. For DigitalOcean Spaces, use format: https://{region}.digitaloceanspaces.com', 400);
            }
            
            return $this->error('Connection test failed: ' . $errorMessage, 500);
        }
    }

    /**
     * Get storage usage statistics
     */
    public function usage(): JsonResponse
    {
        $this->authorize('viewAny', StorageSetting::class);

        try {
            $usage = $this->service->getUsage();
            return $this->success($usage);
        } catch (\Exception $e) {
            return $this->error('Failed to get storage usage: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Migrate storage
     */
    public function migrate(MigrateStorageRequest $request): JsonResponse
    {
        $this->authorize('create', StorageSetting::class);

        try {
            $validated = $request->validated();
            $result = $this->service->migrateStorage(
                $validated['source_id'],
                $validated['destination_id']
            );
            
            return $this->success($result, 'Storage migration completed');
        } catch (\Exception $e) {
            return $this->error('Migration failed: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Clean unused files
     */
    public function cleanup(CleanupStorageRequest $request): JsonResponse
    {
        $this->authorize('create', StorageSetting::class);

        try {
            $olderThanDays = $request->validated()['older_than_days'] ?? 30;
            $result = $this->service->cleanUnusedFiles($olderThanDays);
            
            return $this->success($result, 'Cleanup completed');
        } catch (\Exception $e) {
            return $this->error('Cleanup failed: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Activate storage configuration
     */
    public function activate(int $id): JsonResponse
    {
        try {
            $setting = StorageSetting::findOrFail($id);
            $this->authorize('update', $setting);
            $setting->activate();
            
            return $this->success(new StorageSettingResource($setting->fresh()), 'Storage configuration activated');
        } catch (\Exception $e) {
            return $this->error('Failed to activate storage configuration: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Delete storage configuration
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $setting = StorageSetting::findOrFail($id);
            $this->authorize('delete', $setting);

            $this->deleteStorageSettingAction->execute($setting);
            
            return $this->success(null, 'Storage configuration deleted successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }
}
