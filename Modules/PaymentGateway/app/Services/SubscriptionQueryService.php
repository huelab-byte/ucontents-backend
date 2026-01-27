<?php

declare(strict_types=1);

namespace Modules\PaymentGateway\Services;

use Modules\PaymentGateway\Models\Subscription;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class SubscriptionQueryService
{
    /**
     * List all subscriptions with filters (admin)
     */
    public function listAllWithFilters(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Subscription::with(['user', 'paymentGateway']);

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['interval'])) {
            $query->where('interval', $filters['interval']);
        }

        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    /**
     * List subscriptions for a user with filters
     */
    public function listForUserWithFilters(int $userId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Subscription::where('user_id', $userId)
            ->with(['paymentGateway']);

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['interval'])) {
            $query->where('interval', $filters['interval']);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }
}
