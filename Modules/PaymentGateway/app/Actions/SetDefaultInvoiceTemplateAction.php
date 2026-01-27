<?php

declare(strict_types=1);

namespace Modules\PaymentGateway\Actions;

use Modules\PaymentGateway\Models\InvoiceTemplate;
use Illuminate\Support\Facades\DB;

/**
 * Action to set an invoice template as the default
 */
class SetDefaultInvoiceTemplateAction
{
    /**
     * Set the given template as the default
     *
     * @throws \InvalidArgumentException if template is not active
     */
    public function execute(InvoiceTemplate $template): InvoiceTemplate
    {
        if (!$template->is_active) {
            throw new \InvalidArgumentException('Cannot set an inactive template as default. Please activate it first.');
        }

        return DB::transaction(function () use ($template) {
            // Remove default from all other templates
            InvoiceTemplate::where('is_default', true)
                ->where('id', '!=', $template->id)
                ->update(['is_default' => false]);

            // Set this template as default
            $template->is_default = true;
            $template->save();

            return $template->fresh();
        });
    }
}
