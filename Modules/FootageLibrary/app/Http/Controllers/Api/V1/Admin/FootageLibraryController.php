<?php

declare(strict_types=1);

namespace Modules\FootageLibrary\Http\Controllers\Api\V1\Admin;

use Modules\Core\Http\Controllers\Api\BaseApiController;
use Modules\FootageLibrary\Models\Footage;
use Modules\FootageLibrary\Http\Resources\FootageResource;
use Modules\FootageLibrary\Http\Requests\ListFootageRequest;
use Modules\FootageLibrary\Actions\DeleteFootageAction;
use Modules\FootageLibrary\Services\FootageQueryService;
use Illuminate\Http\JsonResponse;

class FootageLibraryController extends BaseApiController
{
    public function __construct(
        private DeleteFootageAction $deleteFootageAction,
        private FootageQueryService $queryService
    ) {}

    /**
     * Get footage statistics
     */
    public function stats(): JsonResponse
    {
        $this->authorize('viewAny', Footage::class);

        $stats = $this->queryService->getStatistics();

        return $this->success($stats, 'Statistics retrieved successfully');
    }

    /**
     * List all footage (admin)
     */
    public function index(ListFootageRequest $request): JsonResponse
    {
        $this->authorize('viewAny', Footage::class);

        $filters = $request->only(['user_id', 'status', 'folder_id']);
        $perPage = (int) $request->input('per_page', 15);

        $footage = $this->queryService->listAllWithFilters($filters, $perPage);

        return $this->paginatedResource($footage, FootageResource::class, 'Footage retrieved successfully');
    }

    /**
     * Delete footage with storage file and Qdrant point (admin)
     */
    public function destroy(int $id): JsonResponse
    {
        $footage = Footage::findOrFail($id);
        $this->authorize('delete', $footage);

        try {
            $this->deleteFootageAction->execute($footage);
            return $this->success(null, 'Footage deleted successfully');
        } catch (\Exception $e) {
            return $this->error('Delete failed: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get users with their footage upload counts
     */
    public function usersWithUploads(): JsonResponse
    {
        $this->authorize('viewAny', Footage::class);

        $users = $this->queryService->getUsersWithUploadCounts();

        return $this->success($users, 'Users with upload counts retrieved successfully');
    }
}
