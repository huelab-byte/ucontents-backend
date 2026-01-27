<?php

declare(strict_types=1);

namespace Modules\AudioLibrary\Http\Controllers\Api\V1\Admin;

use Modules\Core\Http\Controllers\Api\BaseApiController;
use Modules\AudioLibrary\Models\Audio;
use Modules\AudioLibrary\Http\Resources\AudioResource;
use Modules\AudioLibrary\Http\Requests\ListAudioRequest;
use Modules\AudioLibrary\Actions\DeleteAudioAction;
use Modules\AudioLibrary\Services\AudioQueryService;
use Illuminate\Http\JsonResponse;

class AudioLibraryController extends BaseApiController
{
    public function __construct(
        private DeleteAudioAction $deleteAudioAction,
        private AudioQueryService $queryService
    ) {}

    /**
     * Get audio statistics
     */
    public function stats(): JsonResponse
    {
        $this->authorize('viewAny', Audio::class);

        $stats = $this->queryService->getStatistics();

        return $this->success($stats, 'Statistics retrieved successfully');
    }

    /**
     * List all audio (admin)
     */
    public function index(ListAudioRequest $request): JsonResponse
    {
        $this->authorize('viewAny', Audio::class);

        $filters = $request->only(['user_id', 'status', 'folder_id']);
        $perPage = (int) $request->input('per_page', 15);

        $audio = $this->queryService->listAllWithFilters($filters, $perPage);

        return $this->paginatedResource($audio, AudioResource::class, 'Audio retrieved successfully');
    }

    /**
     * Delete audio with storage file (admin)
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
}
