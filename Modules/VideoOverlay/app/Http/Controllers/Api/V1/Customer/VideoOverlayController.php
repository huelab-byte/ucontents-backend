<?php

declare(strict_types=1);

namespace Modules\VideoOverlay\Http\Controllers\Api\V1\Customer;

use Modules\Core\Http\Controllers\Api\BaseApiController;
use Modules\VideoOverlay\Http\Requests\UploadVideoOverlayRequest;
use Modules\VideoOverlay\Http\Requests\CreateFolderRequest;
use Modules\VideoOverlay\Http\Requests\UpdateVideoOverlayRequest;
use Modules\VideoOverlay\Http\Requests\ListVideoOverlayRequest;
use Modules\VideoOverlay\Http\Requests\ListVideoOverlayFoldersRequest;
use Modules\VideoOverlay\Services\VideoOverlayUploadService;
use Modules\VideoOverlay\Actions\CreateFolderAction;
use Modules\VideoOverlay\Actions\UpdateVideoOverlayAction;
use Modules\VideoOverlay\Actions\UpdateFolderAction;
use Modules\VideoOverlay\Actions\DeleteVideoOverlayAction;
use Modules\VideoOverlay\Actions\DeleteFolderAction;
use Modules\VideoOverlay\Services\VideoOverlayQueryService;
use Modules\VideoOverlay\Models\VideoOverlay;
use Modules\VideoOverlay\Models\VideoOverlayFolder;
use Modules\VideoOverlay\DTOs\FolderDTO;
use Modules\VideoOverlay\DTOs\UpdateVideoOverlayDTO;
use Modules\VideoOverlay\DTOs\UpdateFolderDTO;
use Modules\VideoOverlay\Http\Resources\VideoOverlayResource;
use Modules\VideoOverlay\Http\Resources\FolderResource;
use Illuminate\Http\JsonResponse;

class VideoOverlayController extends BaseApiController
{
    public function __construct(
        private VideoOverlayUploadService $uploadService,
        private CreateFolderAction $createFolderAction,
        private UpdateVideoOverlayAction $updateVideoOverlayAction,
        private UpdateFolderAction $updateFolderAction,
        private DeleteVideoOverlayAction $deleteVideoOverlayAction,
        private DeleteFolderAction $deleteFolderAction,
        private VideoOverlayQueryService $queryService
    ) {}

    /**
     * Upload a single video overlay file
     */
    public function upload(UploadVideoOverlayRequest $request): JsonResponse
    {
        $this->authorize('create', VideoOverlay::class);

        try {
            $file = $request->file('file');
            $folderId = $request->input('folder_id');
            
            $videoOverlay = $this->uploadService->upload(
                $file,
                $folderId !== null ? (int) $folderId : null,
                $request->input('title')
            );

            // Load the storageFile relationship so URL is included in response
            $videoOverlay->load('storageFile');

            return $this->success(new VideoOverlayResource($videoOverlay), 'Video overlay uploaded successfully', 201);
        } catch (\Exception $e) {
            return $this->error('Upload failed: ' . $e->getMessage(), 500);
        }
    }

    /**
     * List video overlays
     */
    public function index(ListVideoOverlayRequest $request): JsonResponse
    {
        $this->authorize('viewAny', VideoOverlay::class);

        $filters = $request->only(['folder_id', 'status', 'orientation']);
        $perPage = (int) $request->input('per_page', 15);

        $videoOverlays = $this->queryService->listForUserWithFilters(auth()->id(), $filters, $perPage);

        return $this->paginatedResource($videoOverlays, VideoOverlayResource::class, 'Video overlays retrieved successfully');
    }

    /**
     * Show single video overlay
     */
    public function show(int $id): JsonResponse
    {
        $videoOverlay = VideoOverlay::with(['storageFile', 'folder'])->findOrFail($id);
        $this->authorize('view', $videoOverlay);

        return $this->success(new VideoOverlayResource($videoOverlay), 'Video overlay retrieved successfully');
    }

    /**
     * Update video overlay
     */
    public function update(UpdateVideoOverlayRequest $request, int $id): JsonResponse
    {
        $videoOverlay = VideoOverlay::findOrFail($id);
        $this->authorize('update', $videoOverlay);

        try {
            $dto = UpdateVideoOverlayDTO::fromArray($request->validated());
            $updatedVideoOverlay = $this->updateVideoOverlayAction->execute($videoOverlay, $dto);

            return $this->success(new VideoOverlayResource($updatedVideoOverlay), 'Video overlay updated successfully');
        } catch (\Exception $e) {
            return $this->error('Update failed: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Delete video overlay with storage file
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
     * List folders
     */
    public function listFolders(ListVideoOverlayFoldersRequest $request): JsonResponse
    {
        $this->authorize('viewAny', VideoOverlay::class);

        $query = VideoOverlayFolder::where('user_id', auth()->id())
            ->with(['parent', 'children'])
            ->withCount('videoOverlays');

        if ($request->has('parent_id')) {
            $query->where('parent_id', $request->input('parent_id'));
        } else {
            $query->whereNull('parent_id');
        }

        $folders = $query->get();

        // Add horizontal and vertical counts for each folder
        $folders->each(function ($folder) {
            $folder->horizontal_count = $folder->videoOverlays()
                ->whereJsonContains('metadata->orientation', 'horizontal')
                ->count();
            $folder->vertical_count = $folder->videoOverlays()
                ->whereJsonContains('metadata->orientation', 'vertical')
                ->count();
        });

        return $this->success(FolderResource::collection($folders), 'Folders retrieved successfully');
    }

    /**
     * Create folder
     */
    public function createFolder(CreateFolderRequest $request): JsonResponse
    {
        $this->authorize('createFolder', VideoOverlayFolder::class);

        try {
            $dto = FolderDTO::fromArray($request->validated());
            $folder = $this->createFolderAction->execute($dto->name, $dto->parentId);

            return $this->success(new FolderResource($folder), 'Folder created successfully', 201);
        } catch (\Exception $e) {
            return $this->error('Folder creation failed: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Update folder
     */
    public function updateFolder(CreateFolderRequest $request, int $id): JsonResponse
    {
        $folder = VideoOverlayFolder::where('user_id', auth()->id())->findOrFail($id);
        $this->authorize('updateFolder', $folder);

        try {
            $dto = UpdateFolderDTO::fromArray($request->validated());
            $updatedFolder = $this->updateFolderAction->execute($folder, $dto);

            return $this->success(new FolderResource($updatedFolder), 'Folder updated successfully');
        } catch (\Exception $e) {
            return $this->error('Folder update failed: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Delete folder with all contents (video overlays, child folders, storage files)
     */
    public function deleteFolder(int $id): JsonResponse
    {
        $folder = VideoOverlayFolder::where('user_id', auth()->id())->findOrFail($id);
        $this->authorize('deleteFolder', $folder);

        try {
            $stats = $this->deleteFolderAction->execute($folder);
            return $this->success($stats, 'Folder and all contents deleted successfully');
        } catch (\Exception $e) {
            return $this->error('Folder deletion failed: ' . $e->getMessage(), 500);
        }
    }
}
