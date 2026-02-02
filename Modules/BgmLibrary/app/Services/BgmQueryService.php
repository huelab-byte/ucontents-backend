<?php

declare(strict_types=1);

namespace Modules\BgmLibrary\Services;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Modules\BgmLibrary\Models\Bgm;
use Modules\BgmLibrary\Models\BgmFolder;

/**
 * Service for building BGM queries with filters
 */
class BgmQueryService
{
    /**
     * List all BGM for admin with filters
     */
    public function listAllWithFilters(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Bgm::with(['storageFile', 'folder', 'user']);

        $this->applyFilters($query, $filters);

        return $query->orderByDesc('created_at')->paginate($perPage);
    }

    /**
     * List BGM for a specific user with filters
     */
    public function listForUserWithFilters(int $userId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Bgm::where('user_id', $userId)
            ->with(['storageFile', 'folder']);

        $this->applyFilters($query, $filters);

        return $query->orderByDesc('created_at')->paginate($perPage);
    }

    /**
     * List customer-visible (shared) BGM for browse/use (read-only). Returns ready items only.
     */
    public function listCustomerVisibleWithFilters(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Bgm::where('status', 'ready')
            ->with(['storageFile', 'folder']);

        $this->applyFilters($query, $filters);

        return $query->orderByDesc('created_at')->paginate($perPage);
    }

    /**
     * List folders that have at least one ready BGM (for customer browse).
     */
    public function listFoldersWithBrowseContent(): Collection
    {
        return BgmFolder::query()
            ->whereHas('bgm', fn ($q) => $q->where('status', 'ready'))
            ->with(['parent', 'children'])
            ->withCount(['bgm' => fn ($q) => $q->where('status', 'ready')])
            ->orderBy('path')
            ->get();
    }

    /**
     * Get BGM statistics (for admin dashboard)
     */
    public function getStatistics(): array
    {
        return [
            'total_bgm' => Bgm::count(),
            'ready_bgm' => Bgm::where('status', 'ready')->count(),
            'processing_bgm' => Bgm::where('status', 'processing')->count(),
            'pending_bgm' => Bgm::where('status', 'pending')->count(),
            'failed_bgm' => Bgm::where('status', 'failed')->count(),
            'total_size' => $this->calculateTotalSize(),
            'total_users_with_uploads' => Bgm::distinct('user_id')->count('user_id'),
        ];
    }

    /**
     * Get BGM statistics for a specific user
     */
    public function getStatisticsForUser(int $userId): array
    {
        $baseQuery = Bgm::where('user_id', $userId);

        return [
            'total_bgm' => (clone $baseQuery)->count(),
            'ready_bgm' => (clone $baseQuery)->where('status', 'ready')->count(),
            'processing_bgm' => (clone $baseQuery)->where('status', 'processing')->count(),
            'pending_bgm' => (clone $baseQuery)->where('status', 'pending')->count(),
            'failed_bgm' => (clone $baseQuery)->where('status', 'failed')->count(),
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
     * Calculate total size of all BGM
     */
    protected function calculateTotalSize(): int
    {
        return Bgm::with('storageFile')
            ->get()
            ->sum(fn($bgm) => $bgm->storageFile?->size ?? 0);
    }

    /**
     * Get users with their BGM upload counts
     */
    public function getUsersWithUploadCounts(): array
    {
        $userIds = Bgm::select('user_id')
            ->selectRaw('COUNT(*) as upload_count')
            ->groupBy('user_id')
            ->orderByDesc('upload_count')
            ->get();

        // Load users separately
        $userIdsArray = $userIds->pluck('user_id')->toArray();
        $users = \Modules\UserManagement\Models\User::whereIn('id', $userIdsArray)
            ->select('id', 'name', 'email')
            ->get()
            ->keyBy('id');

        return $userIds->map(function ($item) use ($users) {
            $user = $users->get($item->user_id);
            return [
                'user_id' => $item->user_id,
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ] : null,
                'upload_count' => (int) $item->upload_count,
            ];
        })->toArray();
    }
}
