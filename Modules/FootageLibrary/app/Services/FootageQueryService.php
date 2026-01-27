<?php

declare(strict_types=1);

namespace Modules\FootageLibrary\Services;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Modules\FootageLibrary\Models\Footage;

/**
 * Service for building footage queries with filters
 */
class FootageQueryService
{
    /**
     * List all footage for admin with filters
     */
    public function listAllWithFilters(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Footage::with(['storageFile', 'folder', 'user']);

        $this->applyFilters($query, $filters);

        return $query->orderByDesc('created_at')->paginate($perPage);
    }

    /**
     * List footage for a specific user with filters
     */
    public function listForUserWithFilters(int $userId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Footage::where('user_id', $userId)
            ->with(['storageFile', 'folder']);

        $this->applyFilters($query, $filters);

        return $query->orderByDesc('created_at')->paginate($perPage);
    }

    /**
     * Get footage statistics (for admin dashboard)
     */
    public function getStatistics(): array
    {
        return [
            'total_footage' => Footage::count(),
            'ready_footage' => Footage::where('status', 'ready')->count(),
            'processing_footage' => Footage::where('status', 'processing')->count(),
            'pending_footage' => Footage::where('status', 'pending')->count(),
            'failed_footage' => Footage::where('status', 'failed')->count(),
            'with_embeddings' => Footage::whereNotNull('embedding_id')->count(),
            'total_size' => $this->calculateTotalSize(),
        ];
    }

    /**
     * Get footage statistics for a specific user
     */
    public function getStatisticsForUser(int $userId): array
    {
        $baseQuery = Footage::where('user_id', $userId);

        return [
            'total_footage' => (clone $baseQuery)->count(),
            'ready_footage' => (clone $baseQuery)->where('status', 'ready')->count(),
            'processing_footage' => (clone $baseQuery)->where('status', 'processing')->count(),
            'pending_footage' => (clone $baseQuery)->where('status', 'pending')->count(),
            'failed_footage' => (clone $baseQuery)->where('status', 'failed')->count(),
            'with_embeddings' => (clone $baseQuery)->whereNotNull('embedding_id')->count(),
        ];
    }

    /**
     * Apply filters to query
     */
    protected function applyFilters(Builder $query, array $filters): void
    {
        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['folder_id'])) {
            $query->where('folder_id', $filters['folder_id']);
        }

        if (isset($filters['orientation'])) {
            $orientations = is_array($filters['orientation']) 
                ? $filters['orientation'] 
                : [$filters['orientation']];

            if (count($orientations) > 0) {
                $query->where(function ($q) use ($orientations) {
                    foreach ($orientations as $orientation) {
                        $q->orWhereJsonContains('metadata->orientation', $orientation);
                    }
                });
            }
        }
    }

    /**
     * Calculate total size of all footage
     */
    protected function calculateTotalSize(): int
    {
        return Footage::with('storageFile')
            ->get()
            ->sum(fn($footage) => $footage->storageFile?->size ?? 0);
    }
}
