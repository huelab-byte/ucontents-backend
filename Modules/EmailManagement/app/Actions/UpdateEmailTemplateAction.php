<?php

declare(strict_types=1);

namespace Modules\EmailManagement\Actions;

use Modules\EmailManagement\DTOs\EmailTemplateDTO;
use Modules\EmailManagement\Models\EmailTemplate;

/**
 * Action to update email template
 */
class UpdateEmailTemplateAction
{
    public function execute(EmailTemplate $template, EmailTemplateDTO $dto): EmailTemplate
    {
        $template->update([
            'name' => $dto->name,
            'slug' => $dto->slug,
            'subject' => $dto->subject,
            'body_html' => $dto->bodyHtml,
            'body_text' => $dto->bodyText,
            'variables' => $dto->variables,
            'category' => $dto->category,
            'is_active' => $dto->isActive,
        ]);

        return $template->fresh();
    }
}
