<?php

declare(strict_types=1);

namespace Modules\MediaUpload\Http\Controllers\Api\V1\Customer;

use Modules\Core\Http\Controllers\Api\BaseApiController;
use Modules\MediaUpload\Http\Requests\BulkUploadRequest;
use Modules\MediaUpload\Http\Requests\UpdateMediaUploadRequest;
use Modules\MediaUpload\Actions\BulkUploadAction;
use Modules\MediaUpload\Actions\DeleteMediaUploadAction;
use Modules\MediaUpload\Http\Resources\MediaUploadResource;
use Modules\MediaUpload\Http\Resources\UploadQueueResource;
use Modules\MediaUpload\Models\MediaUpload;
use Modules\MediaUpload\Models\MediaUploadQueue;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MediaUploadController extends BaseApiController
{
    public function __construct(
        private BulkUploadAction $bulkUploadAction,
        private DeleteMediaUploadAction $deleteMediaUploadAction
    ) {}

    public function bulkUpload(BulkUploadRequest $request): JsonResponse
    {
        $this->authorize('create', MediaUpload::class);
        $files = $request->file('files');
        $folderId = (int) $request->input('folder_id');
        $captionConfig = $request->input('caption_config');
        if (is_string($captionConfig)) {
            $captionConfig = json_decode($captionConfig, true) ?: null;
        }
        $items = $this->bulkUploadAction->execute($files, $folderId, auth()->id(), $captionConfig);
        return $this->success([
            'queued_items' => UploadQueueResource::collection($items),
            'count' => count($items),
        ], 'Files queued for upload', 202);
    }

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', MediaUpload::class);
        $query = MediaUpload::where('user_id', auth()->id())
            ->with('storageFile')
            ->orderByDesc('created_at');

        if ($request->has('folder_id')) {
            $query->where('folder_id', (int) $request->input('folder_id'));
        }
        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        $perPage = min((int) ($request->input('per_page') ?? 15), 100);
        $paginator = $query->paginate($perPage);
        return $this->paginatedResource($paginator, MediaUploadResource::class, 'Media uploads retrieved');
    }

    public function show(int $id): JsonResponse
    {
        $upload = MediaUpload::where('user_id', auth()->id())->with('storageFile')->findOrFail($id);
        $this->authorize('view', $upload);
        return $this->success(new MediaUploadResource($upload), 'Media upload retrieved');
    }

    public function update(UpdateMediaUploadRequest $request, int $id): JsonResponse
    {
        $upload = MediaUpload::where('user_id', auth()->id())->findOrFail($id);
        $this->authorize('update', $upload);
        $upload->update($request->validated());
        return $this->success(new MediaUploadResource($upload->fresh('storageFile')), 'Media upload updated');
    }

    public function destroy(int $id): JsonResponse
    {
        $upload = MediaUpload::where('user_id', auth()->id())->findOrFail($id);
        $this->authorize('delete', $upload);
        $this->deleteMediaUploadAction->execute($upload);
        return $this->success(null, 'Media upload deleted');
    }

    public function getQueueStatus(int $id): JsonResponse
    {
        $item = MediaUploadQueue::where('user_id', auth()->id())->findOrFail($id);
        return $this->success(new UploadQueueResource($item), 'Queue status retrieved');
    }

    public function listQueue(Request $request): JsonResponse
    {
        $query = MediaUploadQueue::where('user_id', auth()->id())->orderByDesc('created_at');
        if ($request->has('folder_id')) {
            $query->where('folder_id', (int) $request->input('folder_id'));
        }
        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }
        $perPage = min((int) ($request->input('per_page') ?? 20), 100);
        $paginator = $query->paginate($perPage);
        return $this->paginatedResource($paginator, UploadQueueResource::class, 'Queue items retrieved');
    }
}
