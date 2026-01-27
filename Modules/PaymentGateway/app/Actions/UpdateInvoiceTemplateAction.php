<?php

declare(strict_types=1);

namespace Modules\PaymentGateway\Actions;

use Modules\PaymentGateway\DTOs\UpdateInvoiceTemplateDTO;
use Modules\PaymentGateway\Models\InvoiceTemplate;

/**
 * Action to update an invoice template
 */
class UpdateInvoiceTemplateAction
{
    public function execute(InvoiceTemplate $template, UpdateInvoiceTemplateDTO $dto): InvoiceTemplate
    {
        if ($dto->name !== null) {
            $template->name = $dto->name;
        }

        if ($dto->slug !== null) {
            $template->slug = $dto->slug;
        }

        if ($dto->description !== null) {
            $template->description = $dto->description;
        }

        if ($dto->headerHtml !== null) {
            $template->header_html = $dto->headerHtml;
        }

        if ($dto->footerHtml !== null) {
            $template->footer_html = $dto->footerHtml;
        }

        if ($dto->settings !== null) {
            $template->settings = $dto->settings;
        }

        if ($dto->isActive !== null) {
            $template->is_active = $dto->isActive;
        }

        if ($dto->isDefault !== null && $dto->isDefault) {
            $template->setAsDefault();
        } elseif ($dto->isDefault === false) {
            $template->is_default = false;
            $template->save();
        }

        $template->save();

        return $template;
    }
}
