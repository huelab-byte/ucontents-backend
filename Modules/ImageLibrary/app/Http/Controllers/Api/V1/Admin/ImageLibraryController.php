<?php

declare(strict_types=1);

namespace Modules\ImageLibrary\Http\Controllers\Api\V1\Admin;

use Modules\Core\Http\Controllers\Api\BaseApiController;
use Modules\ImageLibrary\Models\Image;
use Modules\ImageLibrary\Http\Resources\ImageResource;
use Modules\ImageLibrary\Http\Requests\ListImagesRequest;
use Modules\ImageLibrary\Actions\DeleteImageAction;
use Modules\ImageLibrary\Services\ImageQueryService;
use Illuminate\Http\JsonResponse;

class ImageLibraryController extends BaseApiController
{
    public function __construct(
        private DeleteImageAction $deleteImageAction,
        private ImageQueryService $queryService
    ) {}

    /**
     * Get image statistics
     */
    public function stats(): JsonResponse
    {
        $this->authorize('viewAny', Image::class);

        $stats = $this->queryService->getStatistics();

        return $this->success($stats, 'Statistics retrieved successfully');
    }

    /**
     * List all images (admin)
     */
    public function index(ListImagesRequest $request): JsonResponse
    {
        $this->authorize('viewAny', Image::class);

        $filters = $request->only(['user_id', 'status', 'folder_id']);
        $perPage = (int) $request->input('per_page', 15);

        $images = $this->queryService->listAllWithFilters($filters, $perPage);

        return $this->paginatedResource($images, ImageResource::class, 'Images retrieved successfully');
    }

    /**
     * Delete image with storage file (admin)
     */
    public function destroy(int $id): JsonResponse
    {
        $image = Image::findOrFail($id);
        $this->authorize('delete', $image);

        try {
            $this->deleteImageAction->execute($image);
            return $this->success(null, 'Image deleted successfully');
        } catch (\Exception $e) {
            return $this->error('Delete failed: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get users with their image upload counts
     */
    public function usersWithUploads(): JsonResponse
    {
        $this->authorize('viewAny', Image::class);

        $users = $this->queryService->getUsersWithUploadCounts();

        return $this->success($users, 'Users with upload counts retrieved successfully');
    }
}
