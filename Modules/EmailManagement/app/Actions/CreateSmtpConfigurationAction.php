<?php

declare(strict_types=1);

namespace Modules\EmailManagement\Actions;

use Modules\EmailManagement\DTOs\SmtpConfigurationDTO;
use Modules\EmailManagement\Models\SmtpConfiguration;

/**
 * Action to create SMTP configuration
 */
class CreateSmtpConfigurationAction
{
    public function execute(SmtpConfigurationDTO $dto): SmtpConfiguration
    {
        $config = SmtpConfiguration::create([
            'name' => $dto->name,
            'host' => $dto->host,
            'port' => $dto->port,
            'encryption' => $dto->encryption,
            'username' => $dto->username,
            'password' => $dto->password,
            'from_address' => $dto->fromAddress,
            'from_name' => $dto->fromName,
            'is_active' => $dto->isActive,
            'is_default' => $dto->isDefault,
            'options' => $dto->options,
        ]);

        if ($dto->isDefault) {
            $config->setAsDefault();
        }

        return $config;
    }
}
