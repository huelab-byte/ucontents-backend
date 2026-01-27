<?php

declare(strict_types=1);

namespace Modules\EmailManagement\Actions;

use Modules\EmailManagement\Models\SmtpConfiguration;

/**
 * Action to delete an SMTP configuration
 */
class DeleteSmtpConfigurationAction
{
    public function execute(SmtpConfiguration $smtpConfiguration): bool
    {
        return $smtpConfiguration->delete();
    }
}
