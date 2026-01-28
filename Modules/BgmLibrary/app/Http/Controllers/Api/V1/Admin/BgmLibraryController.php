<?php

declare(strict_types=1);

namespace Modules\BgmLibrary\Http\Controllers\Api\V1\Admin;

use Modules\Core\Http\Controllers\Api\BaseApiController;
use Modules\BgmLibrary\Models\Bgm;
use Modules\BgmLibrary\Http\Resources\BgmResource;
use Modules\BgmLibrary\Http\Requests\ListBgmRequest;
use Modules\BgmLibrary\Actions\DeleteBgmAction;
use Modules\BgmLibrary\Services\BgmQueryService;
use Illuminate\Http\JsonResponse;

class BgmLibraryController extends BaseApiController
{
    public function __construct(
        private DeleteBgmAction $deleteBgmAction,
        private BgmQueryService $queryService
    ) {}

    /**
     * Get BGM statistics
     */
    public function stats(): JsonResponse
    {
        $this->authorize('viewAny', Bgm::class);

        $stats = $this->queryService->getStatistics();

        return $this->success($stats, 'Statistics retrieved successfully');
    }

    /**
     * List all BGM (admin)
     */
    public function index(ListBgmRequest $request): JsonResponse
    {
        $this->authorize('viewAny', Bgm::class);

        $filters = $request->only(['user_id', 'status', 'folder_id']);
        $perPage = (int) $request->input('per_page', 15);

        $bgm = $this->queryService->listAllWithFilters($filters, $perPage);

        return $this->paginatedResource($bgm, BgmResource::class, 'BGM retrieved successfully');
    }

    /**
     * Delete BGM with storage file (admin)
     */
    public function destroy(int $id): JsonResponse
    {
        $bgm = Bgm::findOrFail($id);
        $this->authorize('delete', $bgm);

        try {
            $this->deleteBgmAction->execute($bgm);
            return $this->success(null, 'BGM deleted successfully');
        } catch (\Exception $e) {
            return $this->error('Delete failed: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get users with their BGM upload counts
     */
    public function usersWithUploads(): JsonResponse
    {
        $this->authorize('viewAny', Bgm::class);

        $users = $this->queryService->getUsersWithUploadCounts();

        return $this->success($users, 'Users with upload counts retrieved successfully');
    }
}
