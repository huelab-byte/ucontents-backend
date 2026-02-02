<?php

declare(strict_types=1);

namespace Modules\PlanManagement\Http\Controllers\Api\V1\Public;

use Illuminate\Http\JsonResponse;
use Modules\Core\Http\Controllers\Api\BaseApiController;
use Modules\PlanManagement\Http\Resources\PlanResource;
use Modules\PlanManagement\Services\PlanQueryService;

class PlanController extends BaseApiController
{
    public function __construct(
        private PlanQueryService $planQueryService
    ) {
    }

    /**
     * List active plans (public - no auth).
     */
    public function index(): JsonResponse
    {
        $plans = $this->planQueryService->listActive(50);

        return $this->paginatedResource($plans, PlanResource::class, 'Plans retrieved successfully');
    }
}
