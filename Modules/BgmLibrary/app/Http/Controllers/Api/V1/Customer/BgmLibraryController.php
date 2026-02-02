<?php

declare(strict_types=1);

namespace Modules\BgmLibrary\Http\Controllers\Api\V1\Customer;

use Modules\Core\Http\Controllers\Api\BaseApiController;
use Modules\BgmLibrary\Http\Requests\UploadBgmRequest;
use Modules\BgmLibrary\Http\Requests\BulkUploadBgmRequest;
use Modules\BgmLibrary\Http\Requests\CreateFolderRequest;
use Modules\BgmLibrary\Http\Requests\UpdateBgmRequest;
use Modules\BgmLibrary\Http\Requests\ListBgmRequest;
use Modules\BgmLibrary\Http\Requests\ListBgmFoldersRequest;
use Modules\BgmLibrary\Services\BgmUploadService;
use Modules\BgmLibrary\Actions\CreateFolderAction;
use Modules\BgmLibrary\Actions\UpdateBgmAction;
use Modules\BgmLibrary\Actions\UpdateFolderAction;
use Modules\BgmLibrary\Actions\DeleteBgmAction;
use Modules\BgmLibrary\Actions\DeleteFolderAction;
use Modules\BgmLibrary\Services\BgmQueryService;
use Modules\BgmLibrary\Models\Bgm;
use Modules\BgmLibrary\Models\BgmFolder;
use Modules\BgmLibrary\Models\BgmUploadQueue;
use Modules\BgmLibrary\DTOs\FolderDTO;
use Modules\BgmLibrary\DTOs\UpdateBgmDTO;
use Modules\BgmLibrary\DTOs\UpdateFolderDTO;
use Modules\BgmLibrary\Http\Resources\BgmResource;
use Modules\BgmLibrary\Http\Resources\BgmUploadQueueResource;
use Modules\BgmLibrary\Http\Resources\FolderResource;
use Illuminate\Http\JsonResponse;

class BgmLibraryController extends BaseApiController
{
    public function __construct(
        private BgmUploadService $uploadService,
        private CreateFolderAction $createFolderAction,
        private UpdateBgmAction $updateBgmAction,
        private UpdateFolderAction $updateFolderAction,
        private DeleteBgmAction $deleteBgmAction,
        private DeleteFolderAction $deleteFolderAction,
        private BgmQueryService $queryService
    ) {}

    /**
     * Upload a single BGM file
     */
    public function upload(UploadBgmRequest $request): JsonResponse
    {
        $this->authorize('create', Bgm::class);

        try {
            $file = $request->file('file');
            $folderId = $request->input('folder_id');
            
            $bgm = $this->uploadService->upload(
                $file,
                $folderId !== null ? (int) $folderId : null,
                $request->input('title')
            );

            // Load the storageFile relationship so URL is included in response
            $bgm->load('storageFile');

            return $this->success(new BgmResource($bgm), 'BGM uploaded successfully', 201);
        } catch (\Exception $e) {
            return $this->error('Upload failed: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Bulk upload BGM files
     */
    public function bulkUpload(BulkUploadBgmRequest $request): JsonResponse
    {
        $this->authorize('create', Bgm::class);

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
     * List BGM
     */
    public function index(ListBgmRequest $request): JsonResponse
    {
        $this->authorize('viewAny', Bgm::class);

        $filters = $request->only(['folder_id', 'status']);
        $perPage = (int) $request->input('per_page', 15);

        $bgm = $this->queryService->listForUserWithFilters(auth()->id(), $filters, $perPage);

        return $this->paginatedResource($bgm, BgmResource::class, 'BGM retrieved successfully');
    }

    /**
     * List folders that have browse-visible (ready) BGM. For customers with use_bgm_library.
     */
    public function browseFolders(): JsonResponse
    {
        if (! auth()->user()->hasPermission('use_bgm_library')) {
            abort(403, 'You do not have permission to browse the shared BGM library.');
        }

        $folders = $this->queryService->listFoldersWithBrowseContent();

        return $this->success(FolderResource::collection($folders), 'Folders retrieved successfully');
    }

    /**
     * Browse shared BGM (read-only). For customers with use_bgm_library.
     */
    public function browseIndex(ListBgmRequest $request): JsonResponse
    {
        if (! auth()->user()->hasPermission('use_bgm_library')) {
            abort(403, 'You do not have permission to browse the shared BGM library.');
        }

        $filters = $request->only(['folder_id', 'status']);
        $filters['status'] = 'ready';
        $perPage = (int) $request->input('per_page', 15);

        $bgm = $this->queryService->listCustomerVisibleWithFilters($filters, $perPage);

        return $this->paginatedResource($bgm, BgmResource::class, 'Shared BGM retrieved successfully');
    }

    /**
     * Show single shared BGM (browse).
     */
    public function browseShow(int $id): JsonResponse
    {
        $bgm = Bgm::with(['storageFile', 'folder'])->findOrFail($id);
        if (! auth()->user()->hasPermission('use_bgm_library')) {
            abort(403, 'You do not have permission to browse the shared BGM library.');
        }
        if ($bgm->status !== 'ready') {
            abort(404);
        }

        return $this->success(new BgmResource($bgm), 'BGM retrieved successfully');
    }

    /**
     * Show single BGM
     */
    public function show(int $id): JsonResponse
    {
        $bgm = Bgm::with(['storageFile', 'folder'])->findOrFail($id);
        $this->authorize('view', $bgm);

        return $this->success(new BgmResource($bgm), 'BGM retrieved successfully');
    }

    /**
     * Update BGM
     */
    public function update(UpdateBgmRequest $request, int $id): JsonResponse
    {
        $bgm = Bgm::findOrFail($id);
        $this->authorize('update', $bgm);

        try {
            $dto = UpdateBgmDTO::fromArray($request->validated());
            $updatedBgm = $this->updateBgmAction->execute($bgm, $dto);

            return $this->success(new BgmResource($updatedBgm), 'BGM updated successfully');
        } catch (\Exception $e) {
            return $this->error('Update failed: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Delete BGM with storage file
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
     * List folders
     */
    public function listFolders(ListBgmFoldersRequest $request): JsonResponse
    {
        $this->authorize('viewAny', Bgm::class);

        $query = BgmFolder::where('user_id', auth()->id())
            ->with(['parent', 'children'])
            ->withCount('bgm');

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
        $this->authorize('create', BgmFolder::class);

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
        $folder = BgmFolder::where('user_id', auth()->id())->findOrFail($id);
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
     * Delete folder with all contents (BGM, child folders, storage files)
     */
    public function deleteFolder(int $id): JsonResponse
    {
        $folder = BgmFolder::where('user_id', auth()->id())->findOrFail($id);
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
        $this->authorize('viewAny', Bgm::class);

        $queueItem = BgmUploadQueue::where('user_id', auth()->id())->findOrFail($id);

        return $this->success(new BgmUploadQueueResource($queueItem), 'Queue status retrieved successfully');
    }
}
