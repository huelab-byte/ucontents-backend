<?php

declare(strict_types=1);

namespace Modules\PaymentGateway\Http\Controllers\Api\V1\Admin;

use Illuminate\Http\JsonResponse;
use Modules\Core\Http\Controllers\Api\BaseApiController;
use Modules\PaymentGateway\Actions\CreateInvoiceTemplateAction;
use Modules\PaymentGateway\Actions\DeleteInvoiceTemplateAction;
use Modules\PaymentGateway\Actions\SetDefaultInvoiceTemplateAction;
use Modules\PaymentGateway\Actions\UpdateInvoiceTemplateAction;
use Modules\PaymentGateway\DTOs\CreateInvoiceTemplateDTO;
use Modules\PaymentGateway\DTOs\UpdateInvoiceTemplateDTO;
use Modules\PaymentGateway\Http\Requests\CreateInvoiceTemplateRequest;
use Modules\PaymentGateway\Http\Requests\ListInvoiceTemplatesRequest;
use Modules\PaymentGateway\Http\Requests\UpdateInvoiceTemplateRequest;
use Modules\PaymentGateway\Http\Resources\InvoiceTemplateResource;
use Modules\PaymentGateway\Models\InvoiceTemplate;

class InvoiceTemplateController extends BaseApiController
{
    public function __construct(
        private CreateInvoiceTemplateAction $createTemplateAction,
        private UpdateInvoiceTemplateAction $updateTemplateAction,
        private DeleteInvoiceTemplateAction $deleteTemplateAction,
        private SetDefaultInvoiceTemplateAction $setDefaultAction
    ) {
    }

    /**
     * List all invoice templates
     */
    public function index(ListInvoiceTemplatesRequest $request): JsonResponse
    {
        $this->authorize('viewAny', InvoiceTemplate::class);

        $validated = $request->validated();
        $query = InvoiceTemplate::query();

        if (isset($validated['is_active'])) {
            $query->where('is_active', $validated['is_active']);
        }

        if (isset($validated['is_default'])) {
            $query->where('is_default', $validated['is_default']);
        }

        if (isset($validated['search'])) {
            $search = $validated['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $templates = $query->orderBy('is_default', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate($validated['per_page'] ?? 15);

        return $this->paginatedResource($templates, InvoiceTemplateResource::class, 'Invoice templates retrieved successfully');
    }

    /**
     * Show a specific invoice template
     */
    public function show(InvoiceTemplate $invoiceTemplate): JsonResponse
    {
        $this->authorize('view', $invoiceTemplate);

        $invoiceTemplate->load('creator');

        return $this->success(
            new InvoiceTemplateResource($invoiceTemplate),
            'Invoice template retrieved successfully'
        );
    }

    /**
     * Create a new invoice template
     */
    public function store(CreateInvoiceTemplateRequest $request): JsonResponse
    {
        $this->authorize('create', InvoiceTemplate::class);

        $dto = CreateInvoiceTemplateDTO::fromArray($request->validated());
        $template = $this->createTemplateAction->execute($dto, $request->user()?->id);

        return $this->created(
            new InvoiceTemplateResource($template),
            'Invoice template created successfully'
        );
    }

    /**
     * Update an invoice template
     */
    public function update(UpdateInvoiceTemplateRequest $request, InvoiceTemplate $invoiceTemplate): JsonResponse
    {
        $this->authorize('update', $invoiceTemplate);

        $dto = UpdateInvoiceTemplateDTO::fromArray($request->validated());
        $template = $this->updateTemplateAction->execute($invoiceTemplate, $dto);

        return $this->success(
            new InvoiceTemplateResource($template),
            'Invoice template updated successfully'
        );
    }

    /**
     * Delete an invoice template
     */
    public function destroy(InvoiceTemplate $invoiceTemplate): JsonResponse
    {
        $this->authorize('delete', $invoiceTemplate);

        if ($invoiceTemplate->is_default) {
            return $this->error('Cannot delete the default template. Please set another template as default first.', 400);
        }

        $this->deleteTemplateAction->execute($invoiceTemplate);

        return $this->success(null, 'Invoice template deleted successfully');
    }

    /**
     * Set an invoice template as the default
     */
    public function setDefault(InvoiceTemplate $invoiceTemplate): JsonResponse
    {
        $this->authorize('update', $invoiceTemplate);

        try {
            $template = $this->setDefaultAction->execute($invoiceTemplate);

            return $this->success(
                new InvoiceTemplateResource($template),
                'Invoice template set as default successfully'
            );
        } catch (\InvalidArgumentException $e) {
            return $this->error($e->getMessage(), 400);
        }
    }
}
