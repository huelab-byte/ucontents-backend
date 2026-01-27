<?php

declare(strict_types=1);

namespace Modules\EmailManagement\Actions;

use Modules\EmailManagement\Models\SmtpConfiguration;

/**
 * Action to set an SMTP configuration as default
 */
class SetDefaultSmtpConfigurationAction
{
    public function execute(SmtpConfiguration $smtpConfiguration): SmtpConfiguration
    {
        // Remove default from other configurations
        SmtpConfiguration::where('id', '!=', $smtpConfiguration->id)
            ->update(['is_default' => false]);

        $smtpConfiguration->update(['is_default' => true]);

        return $smtpConfiguration->fresh();
    }
}
