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
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class MediaUploadController extends BaseApiController
{
    public function __construct(
        private BulkUploadAction $bulkUploadAction,
        private DeleteMediaUploadAction $deleteMediaUploadAction
    ) {
    }

    public function bulkUpload(BulkUploadRequest $request): JsonResponse
    {
        $this->authorize('create', MediaUpload::class);
        $files = $request->file('files');
        $folderIds = $request->input('folder_ids');
        $folderIdOrIds = is_array($folderIds) && count($folderIds) > 0
            ? array_map('intval', array_values($folderIds))
            : (int) $request->input('folder_id');
        $captionConfig = $request->input('caption_config');
        if (is_string($captionConfig)) {
            $captionConfig = json_decode($captionConfig, true) ?: null;
        }
        $items = $this->bulkUploadAction->execute($files, $folderIdOrIds, auth()->id(), $captionConfig);
        return $this->success([
            'queued_items' => UploadQueueResource::collection($items),
            'count' => count($items),
        ], 'Files queued for upload', 202);
    }

    public function initChunkUpload(Request $request): JsonResponse
    {
        $this->authorize('create', MediaUpload::class);
        $request->validate([
            'folder_id' => 'required|integer|exists:media_upload_folders,id',
            'filename' => 'required|string',
            'total_chunks' => 'required|integer|min:1',
            'caption_config' => 'sometimes|nullable',
        ]);

        $uuid = Str::uuid()->toString();
        $folderId = (int) $request->input('folder_id');
        $captionConfig = $request->input('caption_config');
        if (is_string($captionConfig)) {
            $captionConfig = json_decode($captionConfig, true) ?: null;
        }

        Cache::put('chunk_upload_' . $uuid, [
            'folder_id' => $folderId,
            'caption_config' => $captionConfig,
            'filename' => $request->input('filename'),
            'total_chunks' => (int) $request->input('total_chunks'),
            'user_id' => auth()->id(),
        ], 86400); // 24 hours

        return $this->success(['upload_id' => $uuid], 'Chunk upload initialized');
    }

    public function uploadChunk(Request $request): JsonResponse
    {
        $this->authorize('create', MediaUpload::class);
        $request->validate([
            'upload_id' => 'required|string',
            'chunk_index' => 'required|integer|min:0',
            'file' => 'required|file',
        ]);

        $uuid = $request->input('upload_id');
        $chunkIndex = $request->input('chunk_index');
        $file = $request->file('file');

        if (!Cache::has('chunk_upload_' . $uuid)) {
            return $this->error('Upload session expired or invalid', 404);
        }

        // Store chunk
        $path = "temp/chunks/{$uuid}/{$chunkIndex}";
        Storage::disk('local')->put($path, file_get_contents($file->getPathname()));

        return $this->success(null, 'Chunk uploaded');
    }

    public function finishChunkUpload(Request $request): JsonResponse
    {
        $this->authorize('create', MediaUpload::class);
        $request->validate([
            'upload_id' => 'required|string',
        ]);

        $uuid = $request->input('upload_id');
        $metadata = Cache::get('chunk_upload_' . $uuid);

        if (!$metadata) {
            return $this->error('Upload session expired or invalid', 404);
        }

        $userId = $metadata['user_id'];
        $folderId = $metadata['folder_id'];
        $filename = $metadata['filename'];
        $totalChunks = $metadata['total_chunks'];

        // Verify all chunks exist
        for ($i = 0; $i < $totalChunks; $i++) {
            if (!Storage::disk('local')->exists("temp/chunks/{$uuid}/{$i}")) {
                return $this->error("Missing chunk {$i}", 400);
            }
        }

        // Combine chunks
        $finalPath = "temp/media-uploads/" . Str::random(40) . '_' . $filename;
        $disk = Storage::disk('local');

        // Ensure directory exists
        if (!$disk->exists('temp/media-uploads')) {
            $disk->makeDirectory('temp/media-uploads');
        }

        // Clean potentially existing file
        if ($disk->exists($finalPath)) {
            $disk->delete($finalPath);
        }

        // Append chunks
        // Using PHP's file append for efficiency instead of loading into memory
        $absFinalPath = $disk->path($finalPath);
        $handle = fopen($absFinalPath, 'a');

        for ($i = 0; $i < $totalChunks; $i++) {
            $chunkPath = $disk->path("temp/chunks/{$uuid}/{$i}");
            $chunkHandle = fopen($chunkPath, 'rb');
            stream_copy_to_stream($chunkHandle, $handle);
            fclose($chunkHandle);
        }
        fclose($handle);

        // Ensure file exists and has content before queuing (avoid job "file not found")
        if (!$disk->exists($finalPath)) {
            $disk->deleteDirectory("temp/chunks/{$uuid}");
            Cache::forget('chunk_upload_' . $uuid);
            return $this->error('Failed to assemble upload file', 500);
        }
        $fileSize = $disk->size($finalPath);
        if ($fileSize <= 0) {
            $disk->delete($finalPath);
            $disk->deleteDirectory("temp/chunks/{$uuid}");
            Cache::forget('chunk_upload_' . $uuid);
            return $this->error('Assembled file is empty', 500);
        }

        $captionConfig = $metadata['caption_config'] ?? null;
        if (is_string($captionConfig)) {
            $captionConfig = json_decode($captionConfig, true) ?: null;
        }

        // Create queue item
        $queueItem = MediaUploadQueue::create([
            'user_id' => $userId,
            'folder_id' => $folderId,
            'file_name' => $filename,
            'file_path' => $finalPath,
            'file_size' => $fileSize,
            'mime_type' => $disk->mimeType($finalPath) ?? 'application/octet-stream',
            'caption_config' => $captionConfig,
            'status' => 'pending',
        ]);

        // Initial status is 'pending'. The DispatchMediaUploadsCommand will pick this up
        // and dispatch it fairly to avoid one user blocking the queue.
        // \Modules\MediaUpload\Jobs\ProcessMediaUploadJob::dispatch($queueItem->id);

        // Cleanup chunks
        $disk->deleteDirectory("temp/chunks/{$uuid}");
        Cache::forget('chunk_upload_' . $uuid);

        return $this->success([
            'queued_items' => [new UploadQueueResource($queueItem)],
            'count' => 1,
        ], 'File assembled and queued', 202);
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
