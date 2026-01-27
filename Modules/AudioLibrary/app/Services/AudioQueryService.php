<?php

declare(strict_types=1);

namespace Modules\AudioLibrary\Services;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Modules\AudioLibrary\Models\Audio;

/**
 * Service for building audio queries with filters
 */
class AudioQueryService
{
    /**
     * List all audio for admin with filters
     */
    public function listAllWithFilters(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Audio::with(['storageFile', 'folder', 'user']);

        $this->applyFilters($query, $filters);

        return $query->orderByDesc('created_at')->paginate($perPage);
    }

    /**
     * List audio for a specific user with filters
     */
    public function listForUserWithFilters(int $userId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Audio::where('user_id', $userId)
            ->with(['storageFile', 'folder']);

        $this->applyFilters($query, $filters);

        return $query->orderByDesc('created_at')->paginate($perPage);
    }

    /**
     * Get audio statistics (for admin dashboard)
     */
    public function getStatistics(): array
    {
        return [
            'total_audio' => Audio::count(),
            'ready_audio' => Audio::where('status', 'ready')->count(),
            'processing_audio' => Audio::where('status', 'processing')->count(),
            'pending_audio' => Audio::where('status', 'pending')->count(),
            'failed_audio' => Audio::where('status', 'failed')->count(),
            'total_size' => $this->calculateTotalSize(),
        ];
    }

    /**
     * Get audio statistics for a specific user
     */
    public function getStatisticsForUser(int $userId): array
    {
        $baseQuery = Audio::where('user_id', $userId);

        return [
            'total_audio' => (clone $baseQuery)->count(),
            'ready_audio' => (clone $baseQuery)->where('status', 'ready')->count(),
            'processing_audio' => (clone $baseQuery)->where('status', 'processing')->count(),
            'pending_audio' => (clone $baseQuery)->where('status', 'pending')->count(),
            'failed_audio' => (clone $baseQuery)->where('status', 'failed')->count(),
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
    }

    /**
     * Calculate total size of all audio
     */
    protected function calculateTotalSize(): int
    {
        return Audio::with('storageFile')
            ->get()
            ->sum(fn($audio) => $audio->storageFile?->size ?? 0);
    }
}
