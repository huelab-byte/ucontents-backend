<?php

declare(strict_types=1);

namespace Modules\PlanManagement\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\PlanManagement\Models\Plan;

class PlanQueryService
{
    /**
     * List plans with filters (admin).
     */
    public function listWithFilters(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Plan::query();

        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        if (isset($filters['subscription_type'])) {
            $query->where('subscription_type', $filters['subscription_type']);
        }

        if (isset($filters['featured'])) {
            $query->where('featured', $filters['featured']);
        }

        if (isset($filters['is_free_plan'])) {
            $query->where('is_free_plan', $filters['is_free_plan']);
        }

        return $query->orderBy('sort_order')->orderBy('id')->paginate($perPage);
    }

    /**
     * List active plans (public / customer).
     */
    public function listActive(int $perPage = 50): LengthAwarePaginator
    {
        return Plan::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->paginate($perPage);
    }
}
