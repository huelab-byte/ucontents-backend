<?php

declare(strict_types=1);

namespace Modules\StorageManagement\Factories;

use Modules\StorageManagement\Contracts\StorageDriverInterface;
use Modules\StorageManagement\Drivers\LocalStorageDriver;
use Modules\StorageManagement\Drivers\DoS3StorageDriver;
use Modules\StorageManagement\Drivers\AwsS3StorageDriver;
use Modules\StorageManagement\Drivers\ContaboS3StorageDriver;
use Modules\StorageManagement\Drivers\CloudflareR2StorageDriver;
use Modules\StorageManagement\Drivers\BackblazeB2StorageDriver;
use Modules\StorageManagement\Models\StorageSetting;

class StorageDriverFactory
{
    /**
     * Create storage driver instance
     *
     * @param string|null $driver Driver name or null to use active
     * @param array|null $config Configuration array or null to load from database
     * @return StorageDriverInterface
     */
    public static function make(?string $driver = null, ?array $config = null): StorageDriverInterface
    {
        if ($driver === null || $config === null) {
            $setting = StorageSetting::getActive();
            if (!$setting) {
                throw new \RuntimeException('No active storage configuration found');
            }
            $driver = $setting->driver;
            $config = $setting->toArray();
        }

        return match ($driver) {
            'local' => new LocalStorageDriver($config),
            'do_s3' => new DoS3StorageDriver($config),
            'aws_s3' => new AwsS3StorageDriver($config),
            'contabo_s3' => new ContaboS3StorageDriver($config),
            'cloudflare_r2' => new CloudflareR2StorageDriver($config),
            'backblaze_b2' => new BackblazeB2StorageDriver($config),
            default => throw new \InvalidArgumentException("Unsupported storage driver: {$driver}"),
        };
    }
}
