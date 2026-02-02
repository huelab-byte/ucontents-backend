<?php

declare(strict_types=1);

namespace Modules\BulkPosting\Http\Controllers\Api\V1\Customer;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\Http\Controllers\Api\BaseApiController;
use Modules\BulkPosting\Actions\CreateCampaignAction;
use Modules\BulkPosting\Actions\DeleteCampaignAction;
use Modules\BulkPosting\Actions\PauseCampaignAction;
use Modules\BulkPosting\Actions\ResumeCampaignAction;
use Modules\BulkPosting\Actions\StartCampaignAction;
use Modules\BulkPosting\Actions\SyncCampaignAction;
use Modules\BulkPosting\Actions\UpdateCampaignAction;
use Modules\BulkPosting\DTOs\CreateCampaignDTO;
use Modules\BulkPosting\DTOs\UpdateCampaignDTO;
use Modules\BulkPosting\Http\Requests\StoreCampaignRequest;
use Modules\BulkPosting\Http\Requests\UpdateCampaignRequest;
use Modules\BulkPosting\Http\Resources\CampaignResource;
use Modules\BulkPosting\Models\BulkPostingCampaign;
use Modules\BulkPosting\Services\CampaignQueryService;
use Modules\BulkPosting\Services\CsvTemplateService;

class CampaignController extends BaseApiController
{
    public function __construct(
        private readonly CampaignQueryService $campaignQueryService,
        private readonly CsvTemplateService $csvTemplateService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', BulkPostingCampaign::class);

        $paginator = $this->campaignQueryService->getCampaignsForUser(
            $request->user()->id,
            (int) $request->get('per_page', 20)
        );

        return $this->paginated($paginator, 'Campaigns retrieved successfully');
    }

    public function store(StoreCampaignRequest $request, CreateCampaignAction $action): JsonResponse
    {
        $this->authorize('create', BulkPostingCampaign::class);

        $data = $request->validated();
        $data['connections'] = $data['connections'] ?? ['channels' => [], 'groups' => []];
        $data['content_source_config'] = $data['content_source_config'] ?? [];

        $dto = CreateCampaignDTO::fromArray($data);
        $campaign = $action->execute($request->user(), $dto);

        $campaign->load(['connections', 'brandLogo']);

        return $this->success(new CampaignResource($campaign), 'Campaign created successfully', 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $campaign = BulkPostingCampaign::with(['connections', 'brandLogo'])->findOrFail($id);
        $this->authorize('view', $campaign);

        return $this->success(new CampaignResource($campaign), 'Campaign retrieved successfully');
    }

    public function update(UpdateCampaignRequest $request, UpdateCampaignAction $action, int $id): JsonResponse
    {
        $campaign = BulkPostingCampaign::findOrFail($id);
        $this->authorize('update', $campaign);

        $data = $request->validated();
        if (isset($data['connections'])) {
            $data['connections'] = $data['connections'];
        }
        if (isset($data['content_source_config'])) {
            $data['content_source_config'] = $data['content_source_config'];
        }

        $dto = UpdateCampaignDTO::fromArray($data);
        $updated = $action->execute($campaign, $dto);
        $updated->load(['connections', 'brandLogo']);

        return $this->success(new CampaignResource($updated), 'Campaign updated successfully');
    }

    public function destroy(DeleteCampaignAction $action, int $id): JsonResponse
    {
        $campaign = BulkPostingCampaign::findOrFail($id);
        $this->authorize('delete', $campaign);

        $action->execute($campaign);

        return $this->success(null, 'Campaign deleted successfully');
    }

    public function pause(Request $request, PauseCampaignAction $action, int $id): JsonResponse
    {
        $campaign = BulkPostingCampaign::findOrFail($id);
        $this->authorize('update', $campaign);

        $updated = $action->execute($campaign);
        $updated->load(['connections', 'brandLogo']);

        return $this->success(new CampaignResource($updated), 'Campaign paused successfully');
    }

    public function resume(Request $request, ResumeCampaignAction $action, int $id): JsonResponse
    {
        $campaign = BulkPostingCampaign::findOrFail($id);
        $this->authorize('update', $campaign);

        $updated = $action->execute($campaign);
        $updated->load(['connections', 'brandLogo']);

        return $this->success(new CampaignResource($updated), 'Campaign resumed successfully');
    }

    public function start(Request $request, StartCampaignAction $action, int $id): JsonResponse
    {
        $campaign = BulkPostingCampaign::findOrFail($id);
        $this->authorize('update', $campaign);

        $updated = $action->execute($campaign);
        $updated->load(['connections', 'brandLogo']);

        return $this->success(new CampaignResource($updated), 'Campaign started successfully');
    }

    public function contentItems(Request $request, int $id): JsonResponse
    {
        $campaign = BulkPostingCampaign::findOrFail($id);
        $this->authorize('view', $campaign);

        $paginator = $this->campaignQueryService->getContentItemsForCampaign(
            $campaign,
            (int) $request->get('per_page', 50)
        );

        return $this->paginated($paginator, 'Content items retrieved');
    }

    public function sync(Request $request, SyncCampaignAction $action, int $id): JsonResponse
    {
        $campaign = BulkPostingCampaign::findOrFail($id);
        $this->authorize('update', $campaign);

        $result = $action->execute($campaign);

        if (isset($result['error'])) {
            return $this->error($result['error'], 400);
        }

        return $this->success([
            'added' => $result['added'],
            'skipped' => $result['skipped'],
            'total' => $result['total'],
        ], "Campaign synced successfully. Added {$result['added']} new items.");
    }

    public function downloadSampleCsv(): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $samplePath = $this->csvTemplateService->getSampleCsvPath();

        return response()->download($samplePath, 'bulk-posting-sample.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }
}
