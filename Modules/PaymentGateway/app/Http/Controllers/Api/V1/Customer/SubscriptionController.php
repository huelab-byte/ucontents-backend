<?php

declare(strict_types=1);

namespace Modules\PaymentGateway\Http\Controllers\Api\V1\Customer;

use Illuminate\Http\JsonResponse;
use Modules\Core\Http\Controllers\Api\BaseApiController;
use Modules\PaymentGateway\Actions\CreateSubscriptionAction;
use Modules\PaymentGateway\DTOs\CreateSubscriptionDTO;
use Modules\PaymentGateway\Http\Requests\CreateSubscriptionRequest;
use Modules\PaymentGateway\Http\Requests\ListSubscriptionsRequest;
use Modules\PaymentGateway\Http\Resources\SubscriptionResource;
use Modules\PaymentGateway\Models\Subscription;
use Modules\PaymentGateway\Services\SubscriptionQueryService;

class SubscriptionController extends BaseApiController
{
    public function __construct(
        private CreateSubscriptionAction $createSubscriptionAction,
        private SubscriptionQueryService $queryService
    ) {
    }

    /**
     * List user's subscriptions
     */
    public function index(ListSubscriptionsRequest $request): JsonResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        $filters = array_filter([
            'status' => $validated['status'] ?? null,
            'interval' => $validated['interval'] ?? null,
        ], fn($v) => $v !== null);

        $subscriptions = $this->queryService->listForUserWithFilters($user->id, $filters, $validated['per_page'] ?? 15);

        return $this->paginatedResource($subscriptions, SubscriptionResource::class, 'Subscriptions retrieved successfully');
    }

    /**
     * Show a specific subscription
     */
    public function show(Subscription $subscription): JsonResponse
    {
        $this->authorize('view', $subscription);

        $subscription->load(['paymentGateway']);

        return $this->success(
            new SubscriptionResource($subscription),
            'Subscription retrieved successfully'
        );
    }

    /**
     * Create a subscription
     */
    public function store(CreateSubscriptionRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['user_id'] = $request->user()->id;

        $dto = CreateSubscriptionDTO::fromArray($data);
        $subscription = $this->createSubscriptionAction->execute($dto);

        return $this->created(
            new SubscriptionResource($subscription),
            'Subscription created successfully'
        );
    }
}
