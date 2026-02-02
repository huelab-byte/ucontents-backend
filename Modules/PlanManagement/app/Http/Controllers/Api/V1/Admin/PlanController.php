<?php

declare(strict_types=1);

namespace Modules\PlanManagement\Http\Controllers\Api\V1\Admin;

use Illuminate\Http\JsonResponse;
use Modules\Core\Http\Controllers\Api\BaseApiController;
use Modules\PlanManagement\Actions\CreatePlanAction;
use Modules\PlanManagement\Actions\SubscribeToPlanAction;
use Modules\PlanManagement\Actions\UpdatePlanAction;
use Modules\PlanManagement\DTOs\CreatePlanDTO;
use Modules\PlanManagement\DTOs\UpdatePlanDTO;
use Modules\PlanManagement\Http\Requests\AssignPlanToUserRequest;
use Modules\PlanManagement\Http\Requests\ListPlansRequest;
use Modules\PlanManagement\Http\Requests\StorePlanRequest;
use Modules\PlanManagement\Http\Requests\UpdatePlanRequest;
use Modules\PlanManagement\Http\Resources\PlanResource;
use Modules\PlanManagement\Models\Plan;
use Modules\PlanManagement\Services\PlanQueryService;
use Modules\PaymentGateway\Http\Resources\InvoiceResource;
use Modules\PaymentGateway\Http\Resources\SubscriptionResource;
use Modules\UserManagement\Models\User;

class PlanController extends BaseApiController
{
    public function __construct(
        private CreatePlanAction $createPlanAction,
        private UpdatePlanAction $updatePlanAction,
        private SubscribeToPlanAction $subscribeToPlanAction,
        private PlanQueryService $planQueryService
    ) {
    }

    public function index(ListPlansRequest $request): JsonResponse
    {
        $this->authorize('viewAny', Plan::class);

        $validated = $request->validated();
        $filters = array_filter([
            'is_active' => $validated['is_active'] ?? null,
            'subscription_type' => $validated['subscription_type'] ?? null,
            'featured' => $validated['featured'] ?? null,
            'is_free_plan' => $validated['is_free_plan'] ?? null,
        ], fn ($v) => $v !== null);
        $perPage = (int) ($validated['per_page'] ?? 15);

        $plans = $this->planQueryService->listWithFilters($filters, $perPage);

        return $this->paginatedResource($plans, PlanResource::class, 'Plans retrieved successfully');
    }

    public function store(StorePlanRequest $request): JsonResponse
    {
        $this->authorize('create', Plan::class);

        $dto = CreatePlanDTO::fromArray($request->validated());
        $plan = $this->createPlanAction->execute($dto);

        return $this->created(new PlanResource($plan), 'Plan created successfully');
    }

    public function show(Plan $plan): JsonResponse
    {
        $this->authorize('view', $plan);

        return $this->success(new PlanResource($plan), 'Plan retrieved successfully');
    }

    public function update(UpdatePlanRequest $request, Plan $plan): JsonResponse
    {
        $this->authorize('update', $plan);

        $dto = UpdatePlanDTO::fromArray($request->validated());
        $plan = $this->updatePlanAction->execute($plan, $dto);

        return $this->success(new PlanResource($plan), 'Plan updated successfully');
    }

    public function destroy(Plan $plan): JsonResponse
    {
        $this->authorize('delete', $plan);

        $plan->delete();

        return $this->success(null, 'Plan deleted successfully');
    }

    /**
     * Assign a plan to a user (admin). Creates subscription or invoice for the user.
     */
    public function assign(AssignPlanToUserRequest $request, Plan $plan): JsonResponse
    {
        $this->authorize('update', $plan);

        $user = User::findOrFail($request->validated('user_id'));

        if (! $plan->is_active) {
            return $this->error('This plan is not available.', 422);
        }

        $result = $this->subscribeToPlanAction->execute($user, $plan, []);

        $data = [
            'subscription' => new SubscriptionResource($result['subscription']),
        ];
        if (isset($result['invoice'])) {
            $data['invoice'] = new InvoiceResource($result['invoice']);
            $data['payment_required'] = $result['payment_required'] ?? true;
        }

        return $this->created($data, 'Plan assigned to customer successfully.');
    }
}
