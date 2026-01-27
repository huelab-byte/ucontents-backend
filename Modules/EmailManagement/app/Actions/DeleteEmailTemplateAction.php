<?php

declare(strict_types=1);

namespace Modules\EmailManagement\Actions;

use Modules\EmailManagement\Models\EmailTemplate;

/**
 * Action to delete an email template
 */
class DeleteEmailTemplateAction
{
    public function execute(EmailTemplate $emailTemplate): bool
    {
        return $emailTemplate->delete();
    }
}
