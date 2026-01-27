<?php

declare(strict_types=1);

namespace Modules\AiIntegration\Actions;

use Modules\AiIntegration\DTOs\AiUsageFilterDTO;
use Modules\AiIntegration\Models\AiUsageLog;

/**
 * Action to calculate AI usage statistics
 */
class GetAiUsageStatisticsAction
{
    /**
     * Execute the action to get usage statistics
     *
     * @param AiUsageFilterDTO $dto
     * @return array<string, mixed>
     */
    public function execute(AiUsageFilterDTO $dto): array
    {
        $query = AiUsageLog::query();

        // Apply date filters
        if ($dto->dateFrom) {
            $query->whereDate('created_at', '>=', $dto->dateFrom);
        }

        if ($dto->dateTo) {
            $query->whereDate('created_at', '<=', $dto->dateTo);
        }

        return [
            'total_requests' => (clone $query)->count(),
            'total_tokens' => (clone $query)->sum('total_tokens'),
            'total_cost' => (clone $query)->sum('cost'),
            'successful_requests' => (clone $query)->where('status', AiUsageLog::STATUS_SUCCESS)->count(),
            'failed_requests' => (clone $query)->where('status', AiUsageLog::STATUS_ERROR)->count(),
            'by_provider' => (clone $query)
                ->selectRaw('provider_slug, COUNT(*) as requests, SUM(total_tokens) as tokens, SUM(cost) as cost')
                ->groupBy('provider_slug')
                ->get()
                ->toArray(),
            'by_model' => (clone $query)
                ->selectRaw('model, COUNT(*) as requests, SUM(total_tokens) as tokens, SUM(cost) as cost')
                ->groupBy('model')
                ->get()
                ->toArray(),
        ];
    }
}
