<?php

declare(strict_types=1);

namespace Modules\VideoOverlay\Http\Controllers\Api\V1\Admin;

use Modules\Core\Http\Controllers\Api\BaseApiController;
use Modules\VideoOverlay\Models\VideoOverlay;
use Modules\VideoOverlay\Http\Resources\VideoOverlayResource;
use Modules\VideoOverlay\Http\Requests\ListVideoOverlayRequest;
use Modules\VideoOverlay\Actions\DeleteVideoOverlayAction;
use Modules\VideoOverlay\Services\VideoOverlayQueryService;
use Illuminate\Http\JsonResponse;

class VideoOverlayController extends BaseApiController
{
    public function __construct(
        private DeleteVideoOverlayAction $deleteVideoOverlayAction,
        private VideoOverlayQueryService $queryService
    ) {}

    /**
     * Get video overlay statistics
     */
    public function stats(): JsonResponse
    {
        $this->authorize('viewAny', VideoOverlay::class);

        $stats = $this->queryService->getStatistics();

        return $this->success($stats, 'Statistics retrieved successfully');
    }

    /**
     * List all video overlays (admin)
     */
    public function index(ListVideoOverlayRequest $request): JsonResponse
    {
        $this->authorize('viewAny', VideoOverlay::class);

        $filters = $request->only(['user_id', 'status', 'folder_id']);
        $perPage = (int) $request->input('per_page', 15);

        $videoOverlays = $this->queryService->listAllWithFilters($filters, $perPage);

        return $this->paginatedResource($videoOverlays, VideoOverlayResource::class, 'Video overlays retrieved successfully');
    }

    /**
     * Delete video overlay with storage file (admin)
     */
    public function destroy(int $id): JsonResponse
    {
        $videoOverlay = VideoOverlay::findOrFail($id);
        $this->authorize('delete', $videoOverlay);

        try {
            $this->deleteVideoOverlayAction->execute($videoOverlay);
            return $this->success(null, 'Video overlay deleted successfully');
        } catch (\Exception $e) {
            return $this->error('Delete failed: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get users with their video overlay upload counts
     */
    public function usersWithUploads(): JsonResponse
    {
        $this->authorize('viewAny', VideoOverlay::class);

        $users = $this->queryService->getUsersWithUploadCounts();

        return $this->success($users, 'Users with upload counts retrieved successfully');
    }
}
