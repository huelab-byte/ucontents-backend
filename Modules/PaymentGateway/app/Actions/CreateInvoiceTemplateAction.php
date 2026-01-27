<?php

declare(strict_types=1);

namespace Modules\PaymentGateway\Actions;

use Modules\PaymentGateway\DTOs\CreateInvoiceTemplateDTO;
use Modules\PaymentGateway\Models\InvoiceTemplate;

/**
 * Action to create an invoice template
 */
class CreateInvoiceTemplateAction
{
    public function execute(CreateInvoiceTemplateDTO $dto, ?int $createdBy = null): InvoiceTemplate
    {
        $template = new InvoiceTemplate();
        $template->name = $dto->name;
        $template->slug = $dto->slug;
        $template->description = $dto->description;
        $template->header_html = $dto->headerHtml;
        $template->footer_html = $dto->footerHtml;
        $template->settings = $dto->settings;
        $template->is_active = $dto->isActive;
        $template->is_default = $dto->isDefault;
        $template->created_by = $createdBy;
        $template->save();

        // If this is set as default, unset other defaults
        if ($dto->isDefault) {
            $template->setAsDefault();
        }

        return $template;
    }
}
