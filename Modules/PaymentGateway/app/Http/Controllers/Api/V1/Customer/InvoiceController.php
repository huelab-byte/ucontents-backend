<?php

declare(strict_types=1);

namespace Modules\PaymentGateway\Http\Controllers\Api\V1\Customer;

use Illuminate\Http\JsonResponse;
use Modules\Core\Http\Controllers\Api\BaseApiController;
use Modules\PaymentGateway\Http\Requests\ListCustomerInvoicesRequest;
use Modules\PaymentGateway\Http\Resources\InvoiceResource;
use Modules\PaymentGateway\Models\Invoice;
use Modules\PaymentGateway\Services\InvoiceQueryService;

class InvoiceController extends BaseApiController
{
    public function __construct(
        private InvoiceQueryService $queryService
    ) {
    }

    /**
     * List user's invoices
     */
    public function index(ListCustomerInvoicesRequest $request): JsonResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        $filters = array_filter([
            'status' => $validated['status'] ?? null,
            'type' => $validated['type'] ?? null,
        ], fn($v) => $v !== null);

        $invoices = $this->queryService->listForUserWithFilters($user->id, $filters, $validated['per_page'] ?? 15);

        return $this->paginatedResource($invoices, InvoiceResource::class, 'Invoices retrieved successfully');
    }

    /**
     * Show a specific invoice
     */
    public function show(Invoice $invoice): JsonResponse
    {
        $this->authorize('view', $invoice);

        $invoice->load(['payments', 'refunds']);

        return $this->success(
            new InvoiceResource($invoice),
            'Invoice retrieved successfully'
        );
    }
}
