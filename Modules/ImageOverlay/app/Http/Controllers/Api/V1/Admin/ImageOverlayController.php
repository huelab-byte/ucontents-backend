<?php

declare(strict_types=1);

namespace Modules\ImageOverlay\Http\Controllers\Api\V1\Admin;

use Modules\Core\Http\Controllers\Api\BaseApiController;
use Modules\ImageOverlay\Models\ImageOverlay;
use Modules\ImageOverlay\Http\Resources\ImageOverlayResource;
use Modules\ImageOverlay\Http\Requests\ListImageOverlayRequest;
use Modules\ImageOverlay\Actions\DeleteImageOverlayAction;
use Modules\ImageOverlay\Services\ImageOverlayQueryService;
use Illuminate\Http\JsonResponse;

class ImageOverlayController extends BaseApiController
{
    public function __construct(
        private DeleteImageOverlayAction $deleteImageOverlayAction,
        private ImageOverlayQueryService $queryService
    ) {}

    /**
     * Get image overlay statistics
     */
    public function stats(): JsonResponse
    {
        $this->authorize('viewAny', ImageOverlay::class);

        $stats = $this->queryService->getStatistics();

        return $this->success($stats, 'Statistics retrieved successfully');
    }

    /**
     * List all image overlays (admin)
     */
    public function index(ListImageOverlayRequest $request): JsonResponse
    {
        $this->authorize('viewAny', ImageOverlay::class);

        $filters = $request->only(['user_id', 'status', 'folder_id']);
        $perPage = (int) $request->input('per_page', 15);

        $imageOverlays = $this->queryService->listAllWithFilters($filters, $perPage);

        return $this->paginatedResource($imageOverlays, ImageOverlayResource::class, 'Image overlays retrieved successfully');
    }

    /**
     * Delete image overlay with storage file (admin)
     */
    public function destroy(int $id): JsonResponse
    {
        $imageOverlay = ImageOverlay::findOrFail($id);
        $this->authorize('delete', $imageOverlay);

        try {
            $this->deleteImageOverlayAction->execute($imageOverlay);
            return $this->success(null, 'Image overlay deleted successfully');
        } catch (\Exception $e) {
            return $this->error('Delete failed: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get users with their image overlay upload counts
     */
    public function usersWithUploads(): JsonResponse
    {
        $this->authorize('viewAny', ImageOverlay::class);

        $users = $this->queryService->getUsersWithUploadCounts();

        return $this->success($users, 'Users with upload counts retrieved successfully');
    }
}
