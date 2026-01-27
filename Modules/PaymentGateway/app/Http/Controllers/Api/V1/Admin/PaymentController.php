<?php

declare(strict_types=1);

namespace Modules\PaymentGateway\Http\Controllers\Api\V1\Admin;

use Illuminate\Http\JsonResponse;
use Modules\Core\Http\Controllers\Api\BaseApiController;
use Modules\PaymentGateway\Http\Requests\ListAdminPaymentsRequest;
use Modules\PaymentGateway\Http\Resources\PaymentResource;
use Modules\PaymentGateway\Models\Payment;
use Modules\PaymentGateway\Services\PaymentQueryService;

class PaymentController extends BaseApiController
{
    public function __construct(
        private PaymentQueryService $queryService
    ) {
    }

    /**
     * List all payments
     */
    public function index(ListAdminPaymentsRequest $request): JsonResponse
    {
        $this->authorize('viewAny', Payment::class);

        $validated = $request->validated();
        $filters = array_filter([
            'status' => $validated['status'] ?? null,
            'user_id' => $validated['user_id'] ?? null,
            'invoice_id' => $validated['invoice_id'] ?? null,
            'gateway_name' => $validated['gateway_name'] ?? null,
        ], fn($v) => $v !== null);

        $payments = $this->queryService->listAllWithFilters($filters, $validated['per_page'] ?? 15);

        return $this->paginatedResource($payments, PaymentResource::class, 'Payments retrieved successfully');
    }

    /**
     * Show a specific payment
     */
    public function show(Payment $payment): JsonResponse
    {
        $this->authorize('view', $payment);

        $payment->load(['invoice', 'user', 'paymentGateway', 'refunds']);

        return $this->success(
            new PaymentResource($payment),
            'Payment retrieved successfully'
        );
    }
}
