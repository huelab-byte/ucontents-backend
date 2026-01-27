<?php

declare(strict_types=1);

namespace Modules\PaymentGateway\Services;

use Modules\PaymentGateway\Models\PaymentGateway;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class PaymentGatewayQueryService
{
    /**
     * List all payment gateways with filters
     */
    public function listWithFilters(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = PaymentGateway::query();

        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        if (isset($filters['name'])) {
            $query->where('name', $filters['name']);
        }

        return $query->paginate($perPage);
    }
}
