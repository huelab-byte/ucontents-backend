<?php

declare(strict_types=1);

namespace Modules\ImageLibrary\Http\Controllers\Api\V1\Customer;

use Modules\Core\Http\Controllers\Api\BaseApiController;
use Modules\ImageLibrary\Http\Requests\UploadImageRequest;
use Modules\ImageLibrary\Http\Requests\BulkUploadImageRequest;
use Modules\ImageLibrary\Http\Requests\CreateFolderRequest;
use Modules\ImageLibrary\Http\Requests\UpdateImageRequest;
use Modules\ImageLibrary\Http\Requests\ListImagesRequest;
use Modules\ImageLibrary\Http\Requests\ListImageFoldersRequest;
use Modules\ImageLibrary\Services\ImageUploadService;
use Modules\ImageLibrary\Actions\CreateFolderAction;
use Modules\ImageLibrary\Actions\UpdateImageAction;
use Modules\ImageLibrary\Actions\UpdateFolderAction;
use Modules\ImageLibrary\Actions\DeleteImageAction;
use Modules\ImageLibrary\Actions\DeleteFolderAction;
use Modules\ImageLibrary\Services\ImageQueryService;
use Modules\ImageLibrary\Models\Image;
use Modules\ImageLibrary\Models\ImageFolder;
use Modules\ImageLibrary\Models\ImageUploadQueue;
use Modules\ImageLibrary\DTOs\FolderDTO;
use Modules\ImageLibrary\DTOs\UpdateImageDTO;
use Modules\ImageLibrary\DTOs\UpdateFolderDTO;
use Modules\ImageLibrary\Http\Resources\ImageResource;
use Modules\ImageLibrary\Http\Resources\ImageUploadQueueResource;
use Modules\ImageLibrary\Http\Resources\FolderResource;
use Illuminate\Http\JsonResponse;

class ImageLibraryController extends BaseApiController
{
    public function __construct(
        private ImageUploadService $uploadService,
        private CreateFolderAction $createFolderAction,
        private UpdateImageAction $updateImageAction,
        private UpdateFolderAction $updateFolderAction,
        private DeleteImageAction $deleteImageAction,
        private DeleteFolderAction $deleteFolderAction,
        private ImageQueryService $queryService
    ) {}

    /**
     * Upload a single image file
     */
    public function upload(UploadImageRequest $request): JsonResponse
    {
        $this->authorize('create', Image::class);

        try {
            $file = $request->file('file');
            $folderId = $request->input('folder_id');
            
            $image = $this->uploadService->upload(
                $file,
                $folderId !== null ? (int) $folderId : null,
                $request->input('title')
            );

            // Load the storageFile relationship so URL is included in response
            $image->load('storageFile');

            return $this->success(new ImageResource($image), 'Image uploaded successfully', 201);
        } catch (\Exception $e) {
            return $this->error('Upload failed: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Bulk upload image files
     */
    public function bulkUpload(BulkUploadImageRequest $request): JsonResponse
    {
        $this->authorize('create', Image::class);

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
     * List images
     */
    public function index(ListImagesRequest $request): JsonResponse
    {
        $this->authorize('viewAny', Image::class);

        $filters = $request->only(['folder_id', 'status']);
        $perPage = (int) $request->input('per_page', 15);

        $images = $this->queryService->listForUserWithFilters(auth()->id(), $filters, $perPage);

        return $this->paginatedResource($images, ImageResource::class, 'Images retrieved successfully');
    }

    /**
     * Show single image
     */
    public function show(int $id): JsonResponse
    {
        $image = Image::with(['storageFile', 'folder'])->findOrFail($id);
        $this->authorize('view', $image);

        return $this->success(new ImageResource($image), 'Image retrieved successfully');
    }

    /**
     * Update image
     */
    public function update(UpdateImageRequest $request, int $id): JsonResponse
    {
        $image = Image::findOrFail($id);
        $this->authorize('update', $image);

        try {
            $dto = UpdateImageDTO::fromArray($request->validated());
            $updatedImage = $this->updateImageAction->execute($image, $dto);

            return $this->success(new ImageResource($updatedImage), 'Image updated successfully');
        } catch (\Exception $e) {
            return $this->error('Update failed: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Delete image with storage file
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
     * List folders
     */
    public function listFolders(ListImageFoldersRequest $request): JsonResponse
    {
        $this->authorize('viewAny', Image::class);

        $query = ImageFolder::where('user_id', auth()->id())
            ->with(['parent', 'children'])
            ->withCount('images');

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
        $this->authorize('create', ImageFolder::class);

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
        $folder = ImageFolder::where('user_id', auth()->id())->findOrFail($id);
        $this->authorize('update', $folder);

        try {
            $dto = UpdateFolderDTO::fromArray($request->validated());
            $updatedFolder = $this->updateFolderAction->execute($folder, $dto);

            return $this->success(new FolderResource($updatedFolder), 'Folder updated successfully');
        } catch (\Exception $e) {
            return $this->error('Folder update failed: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Delete folder with all contents (images, child folders, storage files)
     */
    public function deleteFolder(int $id): JsonResponse
    {
        $folder = ImageFolder::where('user_id', auth()->id())->findOrFail($id);
        $this->authorize('delete', $folder);

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
        $this->authorize('viewAny', Image::class);

        $queueItem = ImageUploadQueue::where('user_id', auth()->id())->findOrFail($id);

        return $this->success(new ImageUploadQueueResource($queueItem), 'Queue status retrieved successfully');
    }
}
