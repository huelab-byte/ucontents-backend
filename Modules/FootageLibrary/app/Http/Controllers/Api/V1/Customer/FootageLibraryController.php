<?php

declare(strict_types=1);

namespace Modules\FootageLibrary\Http\Controllers\Api\V1\Customer;

use Modules\Core\Http\Controllers\Api\BaseApiController;
use Modules\FootageLibrary\Http\Requests\UploadFootageRequest;
use Modules\FootageLibrary\Http\Requests\BulkUploadFootageRequest;
use Modules\FootageLibrary\Http\Requests\SearchFootageRequest;
use Modules\FootageLibrary\Http\Requests\GenerateMetadataRequest;
use Modules\FootageLibrary\Http\Requests\CreateFolderRequest;
use Modules\FootageLibrary\Http\Requests\UpdateFootageRequest;
use Modules\FootageLibrary\Http\Requests\ListFootageRequest;
use Modules\FootageLibrary\Http\Requests\ListFootageFoldersRequest;
use Modules\FootageLibrary\Services\FootageUploadService;
use Modules\FootageLibrary\Actions\SearchFootageAction;
use Modules\FootageLibrary\Actions\CreateFolderAction;
use Modules\FootageLibrary\Actions\UpdateFootageAction;
use Modules\FootageLibrary\Actions\UpdateFolderAction;
use Modules\FootageLibrary\Actions\UpdateFootageMetadataAction;
use Modules\FootageLibrary\Actions\DeleteFootageAction;
use Modules\FootageLibrary\Actions\DeleteFolderAction;
use Modules\FootageLibrary\Services\MetadataGenerationService;
use Modules\FootageLibrary\Services\FootageQueryService;
use Modules\FootageLibrary\Models\Footage;
use Modules\FootageLibrary\Models\FootageFolder;
use Modules\FootageLibrary\Models\FootageUploadQueue;
use Modules\FootageLibrary\DTOs\SearchFootageDTO;
use Modules\FootageLibrary\DTOs\FolderDTO;
use Modules\FootageLibrary\DTOs\UpdateFootageDTO;
use Modules\FootageLibrary\DTOs\UpdateFolderDTO;
use Modules\FootageLibrary\Http\Resources\FootageResource;
use Modules\FootageLibrary\Http\Resources\FootageUploadQueueResource;
use Modules\FootageLibrary\Http\Resources\FolderResource;
use Modules\FootageLibrary\Http\Resources\SearchResultResource;
use Illuminate\Http\JsonResponse;

class FootageLibraryController extends BaseApiController
{
    public function __construct(
        private FootageUploadService $uploadService,
        private SearchFootageAction $searchAction,
        private CreateFolderAction $createFolderAction,
        private UpdateFootageAction $updateFootageAction,
        private UpdateFolderAction $updateFolderAction,
        private UpdateFootageMetadataAction $updateMetadataAction,
        private MetadataGenerationService $metadataService,
        private DeleteFootageAction $deleteFootageAction,
        private DeleteFolderAction $deleteFolderAction,
        private FootageQueryService $queryService
    ) {}

    /**
     * Upload a single footage file
     */
    public function upload(UploadFootageRequest $request): JsonResponse
    {
        $this->authorize('create', Footage::class);

        try {
            $file = $request->file('file');
            $folderId = $request->input('folder_id');
            
            $footage = $this->uploadService->upload(
                $file,
                $folderId !== null ? (int) $folderId : null,
                $request->input('title'),
                $request->input('metadata_source', 'title')
            );

            // Load the storageFile relationship so URL is included in response
            $footage->load('storageFile');

            return $this->success(new FootageResource($footage), 'Footage uploaded successfully', 201);
        } catch (\Exception $e) {
            return $this->error('Upload failed: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Bulk upload footage files
     */
    public function bulkUpload(BulkUploadFootageRequest $request): JsonResponse
    {
        $this->authorize('create', Footage::class);

        try {
            $files = $request->file('files');
            $folderId = $request->input('folder_id');
            
            $queuedItems = $this->uploadService->bulkUpload(
                $files,
                $folderId !== null ? (int) $folderId : null,
                $request->input('metadata_source', 'title')
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
     * List footage
     */
    public function index(ListFootageRequest $request): JsonResponse
    {
        $this->authorize('viewAny', Footage::class);

        $filters = $request->only(['folder_id', 'status', 'orientation']);
        $perPage = (int) $request->input('per_page', 15);

        $footage = $this->queryService->listForUserWithFilters(auth()->id(), $filters, $perPage);

        return $this->paginatedResource($footage, FootageResource::class, 'Footage retrieved successfully');
    }

    /**
     * Show single footage
     */
    public function show(int $id): JsonResponse
    {
        $footage = Footage::with(['storageFile', 'folder'])->findOrFail($id);
        $this->authorize('view', $footage);

        return $this->success(new FootageResource($footage), 'Footage retrieved successfully');
    }

    /**
     * Update footage
     */
    public function update(UpdateFootageRequest $request, int $id): JsonResponse
    {
        $footage = Footage::findOrFail($id);
        $this->authorize('update', $footage);

        try {
            $dto = UpdateFootageDTO::fromArray($request->validated());
            $updatedFootage = $this->updateFootageAction->execute($footage, $dto);

            return $this->success(new FootageResource($updatedFootage), 'Footage updated successfully');
        } catch (\Exception $e) {
            return $this->error('Update failed: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Delete footage with storage file and Qdrant point
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
     * Generate metadata for footage
     */
    public function generateMetadata(GenerateMetadataRequest $request, int $id): JsonResponse
    {
        $footage = Footage::with('storageFile')->findOrFail($id);
        $this->authorize('update', $footage);

        $videoPath = null;
        $storageFile = null;

        try {
            $metadataSource = $request->input('metadata_source');
            $storageFile = $footage->storageFile;

            if (!$storageFile) {
                return $this->error('Storage file not found', 404);
            }

            // Get local path using StorageManagement (supports local and remote storage)
            $videoPath = $storageFile->getLocalPath();

            if ($metadataSource === 'frames') {
                $metadata = $this->metadataService->generateFromFrames($videoPath, $footage->title, $footage->user_id);
            } else {
                $metadata = $this->metadataService->generateFromTitle($footage->title, $footage->user_id);
            }

            $this->updateMetadataAction->execute($footage, $metadata);

            return $this->success(new FootageResource($footage->fresh(['storageFile', 'folder'])), 'Metadata generated successfully');
        } catch (\Exception $e) {
            return $this->error('Metadata generation failed: ' . $e->getMessage(), 500);
        } finally {
            // Cleanup temporary file if remote storage was used
            if ($videoPath && $storageFile) {
                $storageFile->cleanupLocalPath($videoPath);
            }
        }
    }

    /**
     * Search footage
     */
    public function search(SearchFootageRequest $request): JsonResponse
    {
        $this->authorize('viewAny', Footage::class);

        try {
            $dto = SearchFootageDTO::fromArray($request->validated());
            $results = $this->searchAction->execute($dto);

            return $this->success(
                SearchResultResource::collection($results),
                'Search completed successfully'
            );
        } catch (\Exception $e) {
            return $this->error('Search failed: ' . $e->getMessage(), 500);
        }
    }

    /**
     * List folders
     */
    public function listFolders(ListFootageFoldersRequest $request): JsonResponse
    {
        $this->authorize('viewAny', Footage::class);

        $query = FootageFolder::where('user_id', auth()->id())
            ->with(['parent', 'children'])
            ->withCount('footage');

        if ($request->has('parent_id')) {
            $query->where('parent_id', $request->input('parent_id'));
        } else {
            $query->whereNull('parent_id');
        }

        $folders = $query->get();

        // Add horizontal and vertical counts for each folder
        $folders->each(function ($folder) {
            $folder->horizontal_count = $folder->footage()
                ->whereJsonContains('metadata->orientation', 'horizontal')
                ->count();
            $folder->vertical_count = $folder->footage()
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
        $this->authorize('create', FootageFolder::class);

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
        $folder = FootageFolder::where('user_id', auth()->id())->findOrFail($id);
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
     * Delete folder with all contents (footage, child folders, storage files, Qdrant points)
     */
    public function deleteFolder(int $id): JsonResponse
    {
        $folder = FootageFolder::where('user_id', auth()->id())->findOrFail($id);
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
        $this->authorize('viewAny', Footage::class);

        $queueItem = FootageUploadQueue::where('user_id', auth()->id())->findOrFail($id);

        return $this->success(new FootageUploadQueueResource($queueItem), 'Queue status retrieved successfully');
    }
}
