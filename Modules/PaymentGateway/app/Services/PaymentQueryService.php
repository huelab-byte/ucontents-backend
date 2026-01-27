<?php

declare(strict_types=1);

namespace Modules\PaymentGateway\Services;

use Modules\PaymentGateway\Models\Payment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class PaymentQueryService
{
    /**
     * List all payments with filters (admin)
     */
    public function listAllWithFilters(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Payment::with(['invoice', 'user', 'paymentGateway']);

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (isset($filters['invoice_id'])) {
            $query->where('invoice_id', $filters['invoice_id']);
        }

        if (isset($filters['gateway_name'])) {
            $query->where('gateway_name', $filters['gateway_name']);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    /**
     * List payments for a user with filters
     */
    public function listForUserWithFilters(int $userId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Payment::where('user_id', $userId)
            ->with(['invoice', 'paymentGateway']);

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['invoice_id'])) {
            $query->where('invoice_id', $filters['invoice_id']);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }
}
