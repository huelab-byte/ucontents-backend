<?php

declare(strict_types=1);

namespace Modules\MediaUpload\Http\Controllers\Api\V1\Customer;

use Modules\Core\Http\Controllers\Api\BaseApiController;
use Modules\MediaUpload\Http\Requests\CreateFolderRequest;
use Modules\MediaUpload\Http\Requests\UpdateFolderRequest;
use Modules\MediaUpload\Http\Requests\ListFoldersRequest;
use Modules\MediaUpload\Http\Requests\UpdateContentSettingsRequest;
use Modules\MediaUpload\Actions\CreateFolderAction;
use Modules\MediaUpload\Actions\UpdateFolderAction;
use Modules\MediaUpload\Actions\DeleteFolderAction;
use Modules\MediaUpload\Actions\UpsertContentSettingsAction;
use Modules\MediaUpload\DTOs\FolderDTO;
use Modules\MediaUpload\DTOs\UpdateFolderDTO;
use Modules\MediaUpload\Http\Resources\FolderResource;
use Modules\MediaUpload\Http\Resources\ContentSettingsResource;
use Modules\MediaUpload\Models\MediaUploadFolder;
use Illuminate\Http\JsonResponse;

class MediaUploadFolderController extends BaseApiController
{
    public function __construct(
        private CreateFolderAction $createFolderAction,
        private UpdateFolderAction $updateFolderAction,
        private DeleteFolderAction $deleteFolderAction,
        private UpsertContentSettingsAction $upsertContentSettingsAction
    ) {}

    public function listFolders(ListFoldersRequest $request): JsonResponse
    {
        $this->authorize('viewAny', MediaUploadFolder::class);

        $query = MediaUploadFolder::where('user_id', auth()->id())
            ->with(['parent', 'children'])
            ->withCount('mediaUploads');

        if ($request->has('parent_id')) {
            $pid = $request->input('parent_id');
            $query->where('parent_id', $pid === null || $pid === '' ? null : (int) $pid);
        } else {
            $query->whereNull('parent_id');
        }

        $folders = $query->get();
        return $this->success(FolderResource::collection($folders), 'Folders retrieved successfully');
    }

    public function createFolder(CreateFolderRequest $request): JsonResponse
    {
        $this->authorize('create', MediaUploadFolder::class);

        try {
            $dto = FolderDTO::fromArray($request->validated());
            $folder = $this->createFolderAction->execute($dto->name, $dto->parentId);
            return $this->success(new FolderResource($folder), 'Folder created successfully', 201);
        } catch (\Exception $e) {
            return $this->error('Folder creation failed: ' . $e->getMessage(), 500);
        }
    }

    public function updateFolder(UpdateFolderRequest $request, int $id): JsonResponse
    {
        $folder = MediaUploadFolder::where('user_id', auth()->id())->findOrFail($id);
        $this->authorize('update', $folder);

        try {
            $dto = UpdateFolderDTO::fromArray($request->validated());
            $folder = $this->updateFolderAction->execute($folder, $dto);
            return $this->success(new FolderResource($folder), 'Folder updated successfully');
        } catch (\Exception $e) {
            return $this->error('Folder update failed: ' . $e->getMessage(), 500);
        }
    }

    public function deleteFolder(int $id): JsonResponse
    {
        $folder = MediaUploadFolder::where('user_id', auth()->id())->findOrFail($id);
        $this->authorize('delete', $folder);

        try {
            $this->deleteFolderAction->execute($folder);
            return $this->success(null, 'Folder deleted successfully');
        } catch (\Exception $e) {
            return $this->error('Folder deletion failed: ' . $e->getMessage(), 500);
        }
    }

    public function show(int $id): JsonResponse
    {
        $folder = MediaUploadFolder::where('user_id', auth()->id())
            ->with(['parent', 'children', 'contentSettings'])
            ->withCount('mediaUploads')
            ->findOrFail($id);
        $this->authorize('view', $folder);
        return $this->success(new FolderResource($folder), 'Folder retrieved successfully');
    }

    public function getContentSettings(int $id): JsonResponse
    {
        $folder = MediaUploadFolder::where('user_id', auth()->id())->findOrFail($id);
        $this->authorize('view', $folder);
        $folder->load('contentSettings');
        $settings = $folder->contentSettings;
        if (!$settings) {
            return $this->success([
                'id' => null,
                'folder_id' => $folder->id,
                'content_source_type' => 'title',
                'ai_prompt_template_id' => null,
                'custom_prompt' => null,
                'heading_length' => 10,
                'heading_emoji' => false,
                'caption_length' => 30,
                'hashtag_count' => 3,
                'default_caption_template_id' => null,
                'default_loop_count' => 1,
                'default_enable_reverse' => false,
            ], 'Content settings retrieved');
        }
        return $this->success(new ContentSettingsResource($settings), 'Content settings retrieved');
    }

    public function updateContentSettings(UpdateContentSettingsRequest $request, int $id): JsonResponse
    {
        $folder = MediaUploadFolder::where('user_id', auth()->id())->findOrFail($id);
        $this->authorize('update', $folder);
        $settings = $this->upsertContentSettingsAction->execute($folder, $request->validated());
        return $this->success(new ContentSettingsResource($settings), 'Content settings updated');
    }
}
