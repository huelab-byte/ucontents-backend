<?php

declare(strict_types=1);

namespace Modules\PaymentGateway\Http\Controllers\Api\V1\Admin;

use Illuminate\Http\JsonResponse;
use Modules\Core\Http\Controllers\Api\BaseApiController;
use Modules\PaymentGateway\Http\Requests\ListAdminSubscriptionsRequest;
use Modules\PaymentGateway\Http\Resources\SubscriptionResource;
use Modules\PaymentGateway\Models\Subscription;
use Modules\PaymentGateway\Services\SubscriptionQueryService;

class SubscriptionController extends BaseApiController
{
    public function __construct(
        private SubscriptionQueryService $queryService
    ) {
    }

    /**
     * List all subscriptions
     */
    public function index(ListAdminSubscriptionsRequest $request): JsonResponse
    {
        $this->authorize('viewAny', Subscription::class);

        $validated = $request->validated();
        $filters = array_filter([
            'status' => $validated['status'] ?? null,
            'interval' => $validated['interval'] ?? null,
            'user_id' => $validated['user_id'] ?? null,
        ], fn($v) => $v !== null);

        $perPage = isset($validated['per_page']) ? (int) $validated['per_page'] : 15;
        $subscriptions = $this->queryService->listAllWithFilters($filters, $perPage);

        return $this->paginatedResource($subscriptions, SubscriptionResource::class, 'Subscriptions retrieved successfully');
    }

    /**
     * Show a specific subscription
     */
    public function show(Subscription $subscription): JsonResponse
    {
        $this->authorize('view', $subscription);

        $subscription->load(['user', 'paymentGateway']);

        return $this->success(
            new SubscriptionResource($subscription),
            'Subscription retrieved successfully'
        );
    }
}
