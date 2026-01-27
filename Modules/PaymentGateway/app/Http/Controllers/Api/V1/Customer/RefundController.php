<?php

declare(strict_types=1);

namespace Modules\PaymentGateway\Http\Controllers\Api\V1\Customer;

use Illuminate\Http\JsonResponse;
use Modules\Core\Http\Controllers\Api\BaseApiController;
use Modules\PaymentGateway\Actions\ProcessRefundAction;
use Modules\PaymentGateway\DTOs\RefundDTO;
use Modules\PaymentGateway\Http\Requests\ListRefundsRequest;
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
     * List user's refunds
     */
    public function index(ListRefundsRequest $request): JsonResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        $filters = array_filter([
            'status' => $validated['status'] ?? null,
        ], fn($v) => $v !== null);

        $refunds = $this->queryService->listForUserWithFilters($user->id, $filters, $validated['per_page'] ?? 15);

        return $this->paginatedResource($refunds, RefundResource::class, 'Refunds retrieved successfully');
    }

    /**
     * Show a specific refund
     */
    public function show(Refund $refund): JsonResponse
    {
        $this->authorize('view', $refund);

        $refund->load(['payment', 'invoice', 'paymentGateway']);

        return $this->success(
            new RefundResource($refund),
            'Refund retrieved successfully'
        );
    }

    /**
     * Request a refund
     */
    public function store(ProcessRefundRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['user_id'] = $request->user()->id;

        $dto = RefundDTO::fromArray($data);
        $refund = $this->processRefundAction->execute($dto);

        return $this->created(
            new RefundResource($refund),
            'Refund request processed successfully'
        );
    }
}
