<?php

declare(strict_types=1);

namespace Modules\ImageOverlay\Services;

use Modules\ImageOverlay\Models\ImageOverlay;
use Modules\ImageOverlay\Models\ImageOverlayFolder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class ImageOverlayQueryService
{
    /**
     * List image overlays for a user with filters
     */
    public function listForUserWithFilters(int $userId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = ImageOverlay::with(['storageFile', 'folder'])
            ->where('user_id', $userId);

        if (isset($filters['folder_id'])) {
            $query->where('folder_id', $filters['folder_id']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    /**
     * List all image overlays with filters (admin)
     */
    public function listAllWithFilters(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = ImageOverlay::with(['storageFile', 'folder', 'user']);

        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (isset($filters['folder_id'])) {
            $query->where('folder_id', $filters['folder_id']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    /**
     * List folders that have at least one ready image overlay (for customer browse).
     */
    public function listFoldersWithBrowseContent(): Collection
    {
        return ImageOverlayFolder::query()
            ->whereHas('imageOverlays', fn ($q) => $q->where('status', 'ready'))
            ->withCount(['imageOverlays as image_overlays_count' => fn ($q) => $q->where('status', 'ready')])
            ->orderBy('path')
            ->get();
    }

    /**
     * List customer-visible (shared) image overlays for browse/use (read-only). Returns ready items only.
     */
    public function listCustomerVisibleWithFilters(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = ImageOverlay::with(['storageFile', 'folder'])
            ->where('status', 'ready');

        if (isset($filters['folder_id'])) {
            $query->where('folder_id', $filters['folder_id']);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    /**
     * Get statistics
     */
    public function getStatistics(?int $userId = null): array
    {
        $query = ImageOverlay::query();
        
        if ($userId) {
            $query->where('user_id', $userId);
        }

        $stats = $query->select([
            DB::raw('COUNT(*) as total_image_overlay'),
            DB::raw("SUM(CASE WHEN status = 'ready' THEN 1 ELSE 0 END) as ready_image_overlay"),
            DB::raw("SUM(CASE WHEN status = 'processing' THEN 1 ELSE 0 END) as processing_image_overlay"),
            DB::raw("SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_image_overlay"),
            DB::raw("SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed_image_overlay"),
        ])->first();

        // Calculate total size from storage files
        $sizeQuery = ImageOverlay::query();
        if ($userId) {
            $sizeQuery->where('user_id', $userId);
        }
        $totalSize = $sizeQuery->join('storage_files', 'image_overlays.storage_file_id', '=', 'storage_files.id')
            ->sum('storage_files.size');

        $baseQuery = ImageOverlay::query();
        if ($userId) {
            $baseQuery->where('user_id', $userId);
        }

        return [
            'total_image_overlay' => (int) $stats->total_image_overlay,
            'ready_image_overlay' => (int) $stats->ready_image_overlay,
            'processing_image_overlay' => (int) $stats->processing_image_overlay,
            'pending_image_overlay' => (int) $stats->pending_image_overlay,
            'failed_image_overlay' => (int) $stats->failed_image_overlay,
            'total_size' => (int) $totalSize,
            'total_users_with_uploads' => $userId ? 1 : ImageOverlay::distinct('user_id')->count('user_id'),
        ];
    }

    /**
     * Get users with their image overlay upload counts
     */
    public function getUsersWithUploadCounts(): array
    {
        $userIds = ImageOverlay::select('user_id')
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
