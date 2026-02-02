<?php

declare(strict_types=1);

namespace Modules\VideoOverlay\Services;

use Modules\VideoOverlay\Models\VideoOverlay;
use Modules\VideoOverlay\Models\VideoOverlayFolder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class VideoOverlayQueryService
{
    /**
     * List video overlays for a user with filters
     */
    public function listForUserWithFilters(int $userId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = VideoOverlay::where('user_id', $userId)
            ->with(['storageFile', 'folder'])
            ->orderBy('created_at', 'desc');

        // Apply filters
        if (isset($filters['folder_id'])) {
            $query->where('folder_id', $filters['folder_id']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['orientation'])) {
            $query->whereJsonContains('metadata->orientation', $filters['orientation']);
        }

        return $query->paginate($perPage);
    }

    /**
     * List all video overlays (admin only)
     */
    public function listAllWithFilters(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = VideoOverlay::with(['storageFile', 'folder', 'user'])
            ->orderBy('created_at', 'desc');

        // Apply filters
        if (isset($filters['folder_id'])) {
            $query->where('folder_id', $filters['folder_id']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (isset($filters['orientation'])) {
            $query->whereJsonContains('metadata->orientation', $filters['orientation']);
        }

        return $query->paginate($perPage);
    }

    /**
     * List customer-visible (shared) video overlays for browse/use (read-only). Returns ready items only.
     */
    public function listCustomerVisibleWithFilters(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = VideoOverlay::where('status', 'ready')
            ->with(['storageFile', 'folder'])
            ->orderBy('created_at', 'desc');

        if (isset($filters['folder_id'])) {
            $query->where('folder_id', $filters['folder_id']);
        }

        if (isset($filters['orientation'])) {
            $query->whereJsonContains('metadata->orientation', $filters['orientation']);
        }

        return $query->paginate($perPage);
    }

    /**
     * List folders that have at least one ready video overlay (for customer browse).
     */
    public function listFoldersWithBrowseContent(): Collection
    {
        return VideoOverlayFolder::query()
            ->whereHas('videoOverlays', fn ($q) => $q->where('status', 'ready'))
            ->with(['parent', 'children'])
            ->withCount(['videoOverlays' => fn ($q) => $q->where('status', 'ready')])
            ->orderBy('path')
            ->get();
    }

    /**
     * Get video overlay statistics (for admin dashboard)
     */
    public function getStatistics(): array
    {
        return [
            'total_video_overlays' => VideoOverlay::count(),
            'ready_video_overlays' => VideoOverlay::where('status', 'ready')->count(),
            'processing_video_overlays' => VideoOverlay::where('status', 'pending')->count(),
            'failed_video_overlays' => VideoOverlay::where('status', 'failed')->count(),
            'total_size' => $this->calculateTotalSize(),
            'total_users_with_uploads' => VideoOverlay::distinct('user_id')->count('user_id'),
        ];
    }

    /**
     * Calculate total size of all video overlays
     */
    protected function calculateTotalSize(): int
    {
        return VideoOverlay::with('storageFile')
            ->get()
            ->sum(fn($videoOverlay) => $videoOverlay->storageFile?->size ?? 0);
    }

    /**
     * Get users with their video overlay upload counts
     */
    public function getUsersWithUploadCounts(): array
    {
        $userIds = VideoOverlay::select('user_id')
            ->selectRaw('COUNT(*) as upload_count')
            ->groupBy('user_id')
            ->orderByDesc('upload_count')
            ->get();

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
