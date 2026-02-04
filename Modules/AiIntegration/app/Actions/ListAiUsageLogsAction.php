<?php

declare(strict_types=1);

namespace Modules\AiIntegration\Actions;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\AiIntegration\DTOs\AiUsageFilterDTO;
use Modules\AiIntegration\Models\AiUsageLog;

/**
 * Action to list AI usage logs with filtering
 */
class ListAiUsageLogsAction
{
    /**
     * Execute the action to list usage logs
     *
     * @param AiUsageFilterDTO $dto
     * @return LengthAwarePaginator
     */
    public function execute(AiUsageFilterDTO $dto): LengthAwarePaginator
    {
        $query = AiUsageLog::query();
        
        // Use separate with() calls to prevent total failure if one relationship is broken
        $query->with(['user', 'apiKey' => function($q) {
            $q->withTrashed()->with('provider');
        }]);

        // Filter by provider
        if ($dto->providerSlug) {
            $query->where('provider_slug', $dto->providerSlug);
        }

        // Filter by user
        if ($dto->userId) {
            $query->where('user_id', $dto->userId);
        }

        // Filter by status
        if ($dto->status) {
            $query->where('status', $dto->status);
        }

        // Filter by date range
        if ($dto->dateFrom) {
            $query->whereDate('created_at', '>=', $dto->dateFrom);
        }

        if ($dto->dateTo) {
            $query->whereDate('created_at', '<=', $dto->dateTo);
        }

        return $query->orderBy('created_at', 'desc')
            ->paginate($dto->perPage);
    }
}
