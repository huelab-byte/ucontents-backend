<?php

declare(strict_types=1);

namespace Modules\AiIntegration\Services;

use Illuminate\Pagination\LengthAwarePaginator;
use Modules\AiIntegration\Models\AiApiKey;

class AiApiKeyQueryService
{
    /**
     * List all API keys with filters
     */
    public function listAllWithFilters(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = AiApiKey::with('provider');

        // Filter by provider
        if (isset($filters['provider_id'])) {
            $query->where('provider_id', $filters['provider_id']);
        }

        // Filter by active status
        if (isset($filters['is_active'])) {
            $query->where('is_active', filter_var($filters['is_active'], FILTER_VALIDATE_BOOLEAN));
        }

        return $query->orderBy('priority', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }
}
