<?php

declare(strict_types=1);

namespace Modules\EmailManagement\Actions;

use Modules\EmailManagement\DTOs\SmtpConfigurationDTO;
use Modules\EmailManagement\Models\SmtpConfiguration;

/**
 * Action to update SMTP configuration
 */
class UpdateSmtpConfigurationAction
{
    public function execute(SmtpConfiguration $config, SmtpConfigurationDTO $dto): SmtpConfiguration
    {
        $updateData = [
            'name' => $dto->name,
            'host' => $dto->host,
            'port' => $dto->port,
            'encryption' => $dto->encryption,
            'username' => $dto->username,
            'from_address' => $dto->fromAddress,
            'from_name' => $dto->fromName,
            'is_active' => $dto->isActive,
            'is_default' => $dto->isDefault,
            'options' => $dto->options,
        ];

        // Only update password if provided
        if ($dto->password !== null) {
            $updateData['password'] = $dto->password;
        }

        $config->update($updateData);

        if ($dto->isDefault) {
            $config->setAsDefault();
        }

        return $config->fresh();
    }
}
