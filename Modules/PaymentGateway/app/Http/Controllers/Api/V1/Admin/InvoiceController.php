<?php

declare(strict_types=1);

namespace Modules\PaymentGateway\Http\Controllers\Api\V1\Admin;

use Illuminate\Http\JsonResponse;
use Modules\Core\Http\Controllers\Api\BaseApiController;
use Modules\PaymentGateway\Actions\DeleteInvoiceAction;
use Modules\PaymentGateway\Actions\GenerateInvoiceAction;
use Modules\PaymentGateway\Actions\UpdateInvoiceAction;
use Modules\PaymentGateway\DTOs\CreateInvoiceDTO;
use Modules\PaymentGateway\DTOs\UpdateInvoiceDTO;
use Modules\PaymentGateway\Http\Requests\CreateInvoiceRequest;
use Modules\PaymentGateway\Http\Requests\UpdateInvoiceRequest;
use Modules\PaymentGateway\Http\Requests\ListAdminInvoicesRequest;
use Modules\PaymentGateway\Http\Resources\InvoiceResource;
use Modules\PaymentGateway\Models\Invoice;
use Modules\PaymentGateway\Services\InvoiceQueryService;

class InvoiceController extends BaseApiController
{
    public function __construct(
        private GenerateInvoiceAction $generateInvoiceAction,
        private UpdateInvoiceAction $updateInvoiceAction,
        private DeleteInvoiceAction $deleteInvoiceAction,
        private InvoiceQueryService $queryService
    ) {
    }

    /**
     * List all invoices
     */
    public function index(ListAdminInvoicesRequest $request): JsonResponse
    {
        $this->authorize('viewAny', Invoice::class);

        $validated = $request->validated();
        $filters = array_filter([
            'status' => $validated['status'] ?? null,
            'type' => $validated['type'] ?? null,
            'user_id' => $validated['user_id'] ?? null,
            'search' => $validated['search'] ?? null,
        ], fn($v) => $v !== null);

        $invoices = $this->queryService->listAllWithFilters($filters, $validated['per_page'] ?? 15);

        return $this->paginatedResource($invoices, InvoiceResource::class, 'Invoices retrieved successfully');
    }

    /**
     * Show a specific invoice
     */
    public function show(Invoice $invoice): JsonResponse
    {
        $this->authorize('view', $invoice);

        $invoice->load(['user', 'payments', 'refunds']);

        return $this->success(
            new InvoiceResource($invoice),
            'Invoice retrieved successfully'
        );
    }

    /**
     * Create a new invoice
     */
    public function store(CreateInvoiceRequest $request): JsonResponse
    {
        $this->authorize('create', Invoice::class);

        $dto = CreateInvoiceDTO::fromArray($request->validated());
        $invoice = $this->generateInvoiceAction->execute($dto, $request->user()?->id);

        return $this->created(
            new InvoiceResource($invoice),
            'Invoice created successfully'
        );
    }

    /**
     * Update an invoice
     */
    public function update(UpdateInvoiceRequest $request, Invoice $invoice): JsonResponse
    {
        $this->authorize('update', $invoice);

        $dto = UpdateInvoiceDTO::fromArray($request->validated());
        $invoice = $this->updateInvoiceAction->execute($invoice, $dto);

        return $this->success(
            new InvoiceResource($invoice),
            'Invoice updated successfully'
        );
    }

    /**
     * Delete an invoice
     */
    public function destroy(Invoice $invoice): JsonResponse
    {
        $this->authorize('delete', $invoice);

        try {
            $this->deleteInvoiceAction->execute($invoice);
            return $this->success(null, 'Invoice deleted successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }
}
