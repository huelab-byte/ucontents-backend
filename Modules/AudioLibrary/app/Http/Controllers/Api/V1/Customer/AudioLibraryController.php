<?php

declare(strict_types=1);

namespace Modules\AudioLibrary\Http\Controllers\Api\V1\Customer;

use Modules\Core\Http\Controllers\Api\BaseApiController;
use Modules\AudioLibrary\Http\Requests\UploadAudioRequest;
use Modules\AudioLibrary\Http\Requests\BulkUploadAudioRequest;
use Modules\AudioLibrary\Http\Requests\CreateFolderRequest;
use Modules\AudioLibrary\Http\Requests\UpdateAudioRequest;
use Modules\AudioLibrary\Http\Requests\ListAudioRequest;
use Modules\AudioLibrary\Http\Requests\ListAudioFoldersRequest;
use Modules\AudioLibrary\Services\AudioUploadService;
use Modules\AudioLibrary\Actions\CreateFolderAction;
use Modules\AudioLibrary\Actions\UpdateAudioAction;
use Modules\AudioLibrary\Actions\UpdateFolderAction;
use Modules\AudioLibrary\Actions\DeleteAudioAction;
use Modules\AudioLibrary\Actions\DeleteFolderAction;
use Modules\AudioLibrary\Services\AudioQueryService;
use Modules\AudioLibrary\Models\Audio;
use Modules\AudioLibrary\Models\AudioFolder;
use Modules\AudioLibrary\Models\AudioUploadQueue;
use Modules\AudioLibrary\DTOs\FolderDTO;
use Modules\AudioLibrary\DTOs\UpdateAudioDTO;
use Modules\AudioLibrary\DTOs\UpdateFolderDTO;
use Modules\AudioLibrary\Http\Resources\AudioResource;
use Modules\AudioLibrary\Http\Resources\AudioUploadQueueResource;
use Modules\AudioLibrary\Http\Resources\FolderResource;
use Illuminate\Http\JsonResponse;

class AudioLibraryController extends BaseApiController
{
    public function __construct(
        private AudioUploadService $uploadService,
        private CreateFolderAction $createFolderAction,
        private UpdateAudioAction $updateAudioAction,
        private UpdateFolderAction $updateFolderAction,
        private DeleteAudioAction $deleteAudioAction,
        private DeleteFolderAction $deleteFolderAction,
        private AudioQueryService $queryService
    ) {}

    /**
     * Upload a single audio file
     */
    public function upload(UploadAudioRequest $request): JsonResponse
    {
        $this->authorize('create', Audio::class);

        try {
            $file = $request->file('file');
            $folderId = $request->input('folder_id');
            
            $audio = $this->uploadService->upload(
                $file,
                $folderId !== null ? (int) $folderId : null,
                $request->input('title')
            );

            // Load the storageFile relationship so URL is included in response
            $audio->load('storageFile');

            return $this->success(new AudioResource($audio), 'Audio uploaded successfully', 201);
        } catch (\Exception $e) {
            return $this->error('Upload failed: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Bulk upload audio files
     */
    public function bulkUpload(BulkUploadAudioRequest $request): JsonResponse
    {
        $this->authorize('create', Audio::class);

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
     * List audio (own items)
     */
    public function index(ListAudioRequest $request): JsonResponse
    {
        $this->authorize('viewAny', Audio::class);

        $filters = $request->only(['folder_id', 'status']);
        $perPage = (int) $request->input('per_page', 15);

        $audio = $this->queryService->listForUserWithFilters(auth()->id(), $filters, $perPage);

        return $this->paginatedResource($audio, AudioResource::class, 'Audio retrieved successfully');
    }

    /**
     * List folders that have browse-visible (ready) audio. For customers with use_audio_library.
     */
    public function browseFolders(): JsonResponse
    {
        if (! auth()->user()->hasPermission('use_audio_library')) {
            abort(403, 'You do not have permission to browse the shared audio library.');
        }

        $folders = $this->queryService->listFoldersWithBrowseContent();

        return $this->success(FolderResource::collection($folders), 'Folders retrieved successfully');
    }

    /**
     * Browse shared audio (read-only). For customers with use_audio_library.
     */
    public function browseIndex(ListAudioRequest $request): JsonResponse
    {
        if (! auth()->user()->hasPermission('use_audio_library')) {
            abort(403, 'You do not have permission to browse the shared audio library.');
        }

        $filters = $request->only(['folder_id', 'status']);
        $filters['status'] = 'ready';
        $perPage = (int) $request->input('per_page', 15);

        $audio = $this->queryService->listCustomerVisibleWithFilters($filters, $perPage);

        return $this->paginatedResource($audio, AudioResource::class, 'Shared audio retrieved successfully');
    }

    /**
     * Show single shared audio (browse).
     */
    public function browseShow(int $id): JsonResponse
    {
        $audio = Audio::with(['storageFile', 'folder'])->findOrFail($id);
        if (! auth()->user()->hasPermission('use_audio_library')) {
            abort(403, 'You do not have permission to browse the shared audio library.');
        }
        if ($audio->status !== 'ready') {
            abort(404);
        }

        return $this->success(new AudioResource($audio), 'Audio retrieved successfully');
    }

    /**
     * Show single audio
     */
    public function show(int $id): JsonResponse
    {
        $audio = Audio::with(['storageFile', 'folder'])->findOrFail($id);
        $this->authorize('view', $audio);

        return $this->success(new AudioResource($audio), 'Audio retrieved successfully');
    }

    /**
     * Update audio
     */
    public function update(UpdateAudioRequest $request, int $id): JsonResponse
    {
        $audio = Audio::findOrFail($id);
        $this->authorize('update', $audio);

        try {
            $dto = UpdateAudioDTO::fromArray($request->validated());
            $updatedAudio = $this->updateAudioAction->execute($audio, $dto);

            return $this->success(new AudioResource($updatedAudio), 'Audio updated successfully');
        } catch (\Exception $e) {
            return $this->error('Update failed: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Delete audio with storage file
     */
    public function destroy(int $id): JsonResponse
    {
        $audio = Audio::findOrFail($id);
        $this->authorize('delete', $audio);

        try {
            $this->deleteAudioAction->execute($audio);
            return $this->success(null, 'Audio deleted successfully');
        } catch (\Exception $e) {
            return $this->error('Delete failed: ' . $e->getMessage(), 500);
        }
    }

    /**
     * List folders
     */
    public function listFolders(ListAudioFoldersRequest $request): JsonResponse
    {
        $this->authorize('viewAny', Audio::class);

        $query = AudioFolder::where('user_id', auth()->id())
            ->with(['parent', 'children'])
            ->withCount('audio');

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
        $this->authorize('create', AudioFolder::class);

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
        $folder = AudioFolder::where('user_id', auth()->id())->findOrFail($id);
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
     * Delete folder with all contents (audio, child folders, storage files)
     */
    public function deleteFolder(int $id): JsonResponse
    {
        $folder = AudioFolder::where('user_id', auth()->id())->findOrFail($id);
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
        $this->authorize('viewAny', Audio::class);

        $queueItem = AudioUploadQueue::where('user_id', auth()->id())->findOrFail($id);

        return $this->success(new AudioUploadQueueResource($queueItem), 'Queue status retrieved successfully');
    }
}
