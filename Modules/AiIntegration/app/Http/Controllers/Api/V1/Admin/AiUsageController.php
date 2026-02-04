<?php

declare(strict_types=1);

namespace Modules\AiIntegration\Http\Controllers\Api\V1\Admin;

use Illuminate\Http\JsonResponse;
use Modules\AiIntegration\Actions\GetAiUsageStatisticsAction;
use Modules\AiIntegration\Actions\ListAiUsageLogsAction;
use Modules\AiIntegration\DTOs\AiUsageFilterDTO;
use Modules\AiIntegration\Http\Requests\ListAiUsageRequest;
use Modules\AiIntegration\Http\Resources\AiUsageLogResource;
use Modules\AiIntegration\Http\Resources\AiUsageStatisticsResource;
use Modules\AiIntegration\Models\AiUsageLog;
use Modules\Core\Http\Controllers\Api\BaseApiController;

/**
 * Admin API Controller for viewing AI usage statistics
 */
class AiUsageController extends BaseApiController
{
    public function __construct(
        private readonly ListAiUsageLogsAction $listAiUsageLogsAction,
        private readonly GetAiUsageStatisticsAction $getAiUsageStatisticsAction
    ) {}

    /**
     * List usage logs
     */
    public function index(ListAiUsageRequest $request): JsonResponse
    {
        try {
            $this->authorize('viewAny', AiUsageLog::class);

            $dto = AiUsageFilterDTO::fromArray($request->validated());
            $logs = $this->listAiUsageLogsAction->execute($dto);

            return $this->paginatedResource(
                $logs,
                AiUsageLogResource::class,
                'Usage logs retrieved successfully'
            );
        } catch (\Throwable $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Get usage statistics
     */
    public function statistics(ListAiUsageRequest $request): JsonResponse
    {
        try {
            $this->authorize('viewAny', AiUsageLog::class);

            $dto = AiUsageFilterDTO::fromArray($request->validated());
            $stats = $this->getAiUsageStatisticsAction->execute($dto);

            return $this->success(new AiUsageStatisticsResource($stats), 'Usage statistics retrieved successfully');
        } catch (\Throwable $e) {
            return $this->handleException($e);
        }
    }
}
