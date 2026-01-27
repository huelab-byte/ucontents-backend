<?php

declare(strict_types=1);

namespace Modules\PaymentGateway\Http\Controllers\Api\V1\Admin;

use Illuminate\Http\JsonResponse;
use Modules\Core\Http\Controllers\Api\BaseApiController;
use Modules\PaymentGateway\Actions\ProcessRefundAction;
use Modules\PaymentGateway\DTOs\RefundDTO;
use Modules\PaymentGateway\Http\Requests\ListAdminRefundsRequest;
use Modules\PaymentGateway\Http\Requests\ProcessRefundRequest;
use Modules\PaymentGateway\Http\Resources\RefundResource;
use Modules\PaymentGateway\Models\Refund;
use Modules\PaymentGateway\Services\RefundQueryService;

class RefundController extends BaseApiController
{
    public function __construct(
        private ProcessRefundAction $processRefundAction,
        private RefundQueryService $queryService
    ) {
    }

    /**
     * List all refunds
     */
    public function index(ListAdminRefundsRequest $request): JsonResponse
    {
        $this->authorize('viewAny', Refund::class);

        $validated = $request->validated();
        $filters = array_filter([
            'status' => $validated['status'] ?? null,
            'user_id' => $validated['user_id'] ?? null,
            'payment_id' => $validated['payment_id'] ?? null,
        ], fn($v) => $v !== null);

        $refunds = $this->queryService->listAllWithFilters($filters, $validated['per_page'] ?? 15);

        return $this->paginatedResource($refunds, RefundResource::class, 'Refunds retrieved successfully');
    }

    /**
     * Show a specific refund
     */
    public function show(Refund $refund): JsonResponse
    {
        $this->authorize('view', $refund);

        $refund->load(['payment', 'invoice', 'user', 'paymentGateway', 'processor']);

        return $this->success(
            new RefundResource($refund),
            'Refund retrieved successfully'
        );
    }

    /**
     * Process a refund
     */
    public function store(ProcessRefundRequest $request): JsonResponse
    {
        $this->authorize('create', Refund::class);

        $data = $request->validated();
        $data['user_id'] = $request->user()?->id ?? $data['user_id'] ?? null;

        if (!$data['user_id']) {
            return $this->error('User ID is required', 400);
        }

        $dto = RefundDTO::fromArray($data);
        $refund = $this->processRefundAction->execute($dto, $request->user()?->id);

        return $this->created(
            new RefundResource($refund),
            'Refund processed successfully'
        );
    }
}
