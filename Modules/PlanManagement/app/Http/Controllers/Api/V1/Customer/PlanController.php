<?php

declare(strict_types=1);

namespace Modules\PlanManagement\Http\Controllers\Api\V1\Customer;

use Illuminate\Http\JsonResponse;
use Modules\Core\Http\Controllers\Api\BaseApiController;
use Modules\PlanManagement\Actions\SubscribeToPlanAction;
use Modules\PlanManagement\Http\Requests\SubscribeToPlanRequest;
use Modules\PlanManagement\Http\Resources\PlanResource;
use Modules\PlanManagement\Models\Plan;
use Modules\PlanManagement\Services\PlanQueryService;
use Modules\PaymentGateway\Http\Resources\InvoiceResource;
use Modules\PaymentGateway\Http\Resources\SubscriptionResource;

class PlanController extends BaseApiController
{
    public function __construct(
        private PlanQueryService $planQueryService,
        private SubscribeToPlanAction $subscribeToPlanAction
    ) {
    }

    /**
     * List active plans (customer - same as public for now).
     */
    public function index(): JsonResponse
    {
        $plans = $this->planQueryService->listActive(50);

        return $this->paginatedResource($plans, PlanResource::class, 'Plans retrieved successfully');
    }

    /**
     * Subscribe to a plan.
     * Recurring: creates subscription via gateway; returns subscription.
     * Lifetime: creates invoice + pending subscription; returns invoice (customer pays via existing payment flow).
     */
    public function subscribe(SubscribeToPlanRequest $request, Plan $plan): JsonResponse
    {
        $user = $request->user();
        if (!$plan->is_active) {
            return $this->error('This plan is not available.', 422);
        }
        if ($plan->price < 0) {
            return $this->error('Invalid plan.', 422);
        }

        $gatewayData = $request->validated()['gateway_data'] ?? [];
        if ($request->filled('gateway_name')) {
            $gatewayData['gateway_name'] = $request->validated()['gateway_name'];
        }

        $result = $this->subscribeToPlanAction->execute($user, $plan, $gatewayData);

        $data = [
            'subscription' => new SubscriptionResource($result['subscription']),
        ];
        if (isset($result['invoice'])) {
            $data['invoice'] = new InvoiceResource($result['invoice']);
            $data['payment_required'] = $result['payment_required'] ?? true;
        }

        return $this->created($data, 'Subscription created. ' . (isset($result['payment_required']) ? 'Please complete payment for your invoice.' : ''));
    }
}
