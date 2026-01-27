<?php

declare(strict_types=1);

namespace Modules\StorageManagement\Drivers;

class AwsS3StorageDriver extends S3StorageDriver
{
    // AWS S3 uses standard S3 endpoints
    // No special configuration needed beyond parent class
}
