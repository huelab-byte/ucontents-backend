<?php

declare(strict_types=1);

namespace Modules\PaymentGateway\Services;

use Modules\PaymentGateway\Models\Refund;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class RefundQueryService
{
    /**
     * List all refunds with filters (admin)
     */
    public function listAllWithFilters(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Refund::with(['payment', 'invoice', 'user', 'paymentGateway']);

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (isset($filters['payment_id'])) {
            $query->where('payment_id', $filters['payment_id']);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    /**
     * List refunds for a user with filters
     */
    public function listForUserWithFilters(int $userId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Refund::where('user_id', $userId)
            ->with(['payment', 'invoice']);

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }
}
