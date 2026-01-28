<?php

declare(strict_types=1);

namespace Modules\ImageLibrary\Services;

use Modules\ImageLibrary\Models\Image;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class ImageQueryService
{
    /**
     * List images for a user with filters
     */
    public function listForUserWithFilters(int $userId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Image::with(['storageFile', 'folder'])
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
     * List all images with filters (admin)
     */
    public function listAllWithFilters(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Image::with(['storageFile', 'folder', 'user']);

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
     * Get statistics
     */
    public function getStatistics(?int $userId = null): array
    {
        $query = Image::query();
        
        if ($userId) {
            $query->where('user_id', $userId);
        }

        $stats = $query->select([
            DB::raw('COUNT(*) as total_image'),
            DB::raw("SUM(CASE WHEN status = 'ready' THEN 1 ELSE 0 END) as ready_image"),
            DB::raw("SUM(CASE WHEN status = 'processing' THEN 1 ELSE 0 END) as processing_image"),
            DB::raw("SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_image"),
            DB::raw("SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed_image"),
        ])->first();

        // Calculate total size from storage files
        $sizeQuery = Image::query();
        if ($userId) {
            $sizeQuery->where('user_id', $userId);
        }
        $totalSize = $sizeQuery->join('storage_files', 'images.storage_file_id', '=', 'storage_files.id')
            ->sum('storage_files.size');

        $baseQuery = Image::query();
        if ($userId) {
            $baseQuery->where('user_id', $userId);
        }

        return [
            'total_image' => (int) $stats->total_image,
            'ready_image' => (int) $stats->ready_image,
            'processing_image' => (int) $stats->processing_image,
            'pending_image' => (int) $stats->pending_image,
            'failed_image' => (int) $stats->failed_image,
            'total_size' => (int) $totalSize,
            'total_users_with_uploads' => $userId ? 1 : Image::distinct('user_id')->count('user_id'),
        ];
    }

    /**
     * Get users with their image upload counts
     */
    public function getUsersWithUploadCounts(): array
    {
        $userIds = Image::select('user_id')
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
