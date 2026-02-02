<?php

declare(strict_types=1);

namespace Modules\ImageOverlay\Http\Controllers\Api\V1\Customer;

use Modules\Core\Http\Controllers\Api\BaseApiController;
use Modules\ImageOverlay\Http\Requests\UploadImageOverlayRequest;
use Modules\ImageOverlay\Http\Requests\BulkUploadImageOverlayRequest;
use Modules\ImageOverlay\Http\Requests\CreateFolderRequest;
use Modules\ImageOverlay\Http\Requests\UpdateImageOverlayRequest;
use Modules\ImageOverlay\Http\Requests\ListImageOverlayRequest;
use Modules\ImageOverlay\Http\Requests\ListImageOverlayFoldersRequest;
use Modules\ImageOverlay\Services\ImageOverlayUploadService;
use Modules\ImageOverlay\Actions\CreateFolderAction;
use Modules\ImageOverlay\Actions\UpdateImageOverlayAction;
use Modules\ImageOverlay\Actions\UpdateFolderAction;
use Modules\ImageOverlay\Actions\DeleteImageOverlayAction;
use Modules\ImageOverlay\Actions\DeleteFolderAction;
use Modules\ImageOverlay\Services\ImageOverlayQueryService;
use Modules\ImageOverlay\Models\ImageOverlay;
use Modules\ImageOverlay\Models\ImageOverlayFolder;
use Modules\ImageOverlay\Models\ImageOverlayUploadQueue;
use Modules\ImageOverlay\DTOs\FolderDTO;
use Modules\ImageOverlay\DTOs\UpdateImageOverlayDTO;
use Modules\ImageOverlay\DTOs\UpdateFolderDTO;
use Modules\ImageOverlay\Http\Resources\ImageOverlayResource;
use Modules\ImageOverlay\Http\Resources\ImageOverlayUploadQueueResource;
use Modules\ImageOverlay\Http\Resources\FolderResource;
use Illuminate\Http\JsonResponse;

class ImageOverlayController extends BaseApiController
{
    public function __construct(
        private ImageOverlayUploadService $uploadService,
        private CreateFolderAction $createFolderAction,
        private UpdateImageOverlayAction $updateImageOverlayAction,
        private UpdateFolderAction $updateFolderAction,
        private DeleteImageOverlayAction $deleteImageOverlayAction,
        private DeleteFolderAction $deleteFolderAction,
        private ImageOverlayQueryService $queryService
    ) {}

    /**
     * Upload a single image overlay file
     */
    public function upload(UploadImageOverlayRequest $request): JsonResponse
    {
        $this->authorize('create', ImageOverlay::class);

        try {
            $file = $request->file('file');
            $folderId = $request->input('folder_id');
            
            $imageOverlay = $this->uploadService->upload(
                $file,
                $folderId !== null ? (int) $folderId : null,
                $request->input('title')
            );

            // Load the storageFile relationship so URL is included in response
            $imageOverlay->load('storageFile');

            return $this->success(new ImageOverlayResource($imageOverlay), 'Image overlay uploaded successfully', 201);
        } catch (\Exception $e) {
            return $this->error('Upload failed: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Bulk upload image overlay files
     */
    public function bulkUpload(BulkUploadImageOverlayRequest $request): JsonResponse
    {
        $this->authorize('create', ImageOverlay::class);

        try {
            $files = $request->file('files');
            $folderId = $request->input('folder_id');
            
            $queuedItems = $this->uploadService->bulkUpload(
                $files,
                $folderId !== null ? (int) $folderId : null
            );

            return $this->success([
                'queued_items' => $queuedItems,
                'count' => count($queuedItems),
            ], 'Files queued for upload', 202);
        } catch (\Exception $e) {
            return $this->error('Bulk upload failed: ' . $e->getMessage(), 500);
        }
    }

    /**
     * List image overlays
     */
    public function index(ListImageOverlayRequest $request): JsonResponse
    {
        $this->authorize('viewAny', ImageOverlay::class);

        $filters = $request->only(['folder_id', 'status']);
        $perPage = (int) $request->input('per_page', 15);

        $imageOverlays = $this->queryService->listForUserWithFilters(auth()->id(), $filters, $perPage);

        return $this->paginatedResource($imageOverlays, ImageOverlayResource::class, 'Image overlays retrieved successfully');
    }

    /**
     * List folders that have browse-visible (ready) image overlays. For customers with use_image_overlay.
     */
    public function browseFolders(): JsonResponse
    {
        if (! auth()->user()->hasPermission('use_image_overlay')) {
            abort(403, 'You do not have permission to browse image overlays.');
        }

        $folders = $this->queryService->listFoldersWithBrowseContent();

        return $this->success(FolderResource::collection($folders), 'Folders retrieved successfully');
    }

    /**
     * Browse image overlays (read-only). For customers with use_image_overlay.
     */
    public function browseIndex(ListImageOverlayRequest $request): JsonResponse
    {
        if (! auth()->user()->hasPermission('use_image_overlay')) {
            abort(403, 'You do not have permission to browse image overlays.');
        }

        $filters = $request->only(['folder_id', 'status']);
        $filters['status'] = 'ready';
        $perPage = (int) $request->input('per_page', 15);

        $imageOverlays = $this->queryService->listCustomerVisibleWithFilters($filters, $perPage);

        return $this->paginatedResource($imageOverlays, ImageOverlayResource::class, 'Image overlays retrieved successfully');
    }

    /**
     * Show single shared image overlay (browse).
     */
    public function browseShow(int $id): JsonResponse
    {
        $imageOverlay = ImageOverlay::with(['storageFile', 'folder'])->findOrFail($id);
        if (! auth()->user()->hasPermission('use_image_overlay')) {
            abort(403, 'You do not have permission to browse image overlays.');
        }
        if ($imageOverlay->status !== 'ready') {
            abort(404);
        }

        return $this->success(new ImageOverlayResource($imageOverlay), 'Image overlay retrieved successfully');
    }

    /**
     * Show single image overlay
     */
    public function show(int $id): JsonResponse
    {
        $imageOverlay = ImageOverlay::with(['storageFile', 'folder'])->findOrFail($id);
        $this->authorize('view', $imageOverlay);

        return $this->success(new ImageOverlayResource($imageOverlay), 'Image overlay retrieved successfully');
    }

    /**
     * Update image overlay
     */
    public function update(UpdateImageOverlayRequest $request, int $id): JsonResponse
    {
        $imageOverlay = ImageOverlay::findOrFail($id);
        $this->authorize('update', $imageOverlay);

        try {
            $dto = UpdateImageOverlayDTO::fromArray($request->validated());
            $updatedImageOverlay = $this->updateImageOverlayAction->execute($imageOverlay, $dto);

            return $this->success(new ImageOverlayResource($updatedImageOverlay), 'Image overlay updated successfully');
        } catch (\Exception $e) {
            return $this->error('Update failed: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Delete image overlay with storage file
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
     * List folders
     */
    public function listFolders(ListImageOverlayFoldersRequest $request): JsonResponse
    {
        $this->authorize('viewAny', ImageOverlay::class);

        $query = ImageOverlayFolder::where('user_id', auth()->id())
            ->with(['parent', 'children'])
            ->withCount('imageOverlays');

        if ($request->has('parent_id')) {
            $query->where('parent_id', $request->input('parent_id'));
        } else {
            $query->whereNull('parent_id');
        }

        $folders = $query->get();

        return $this->success(FolderResource::collection($folders), 'Folders retrieved successfully');
    }

    /**
     * Create folder
     */
    public function createFolder(CreateFolderRequest $request): JsonResponse
    {
        $this->authorize('createFolder', ImageOverlayFolder::class);

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
        $folder = ImageOverlayFolder::where('user_id', auth()->id())->findOrFail($id);
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
     * Delete folder with all contents (image overlays, child folders, storage files)
     */
    public function deleteFolder(int $id): JsonResponse
    {
        $folder = ImageOverlayFolder::where('user_id', auth()->id())->findOrFail($id);
        $this->authorize('deleteFolder', $folder);

        try {
            $stats = $this->deleteFolderAction->execute($folder);
            return $this->success($stats, 'Folder and all contents deleted successfully');
        } catch (\Exception $e) {
            return $this->error('Folder deletion failed: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get upload queue status
     */
    public function getUploadQueueStatus(int $id): JsonResponse
    {
        $this->authorize('viewAny', ImageOverlay::class);

        $queueItem = ImageOverlayUploadQueue::where('user_id', auth()->id())->findOrFail($id);

        return $this->success(new ImageOverlayUploadQueueResource($queueItem), 'Queue status retrieved successfully');
    }
}
