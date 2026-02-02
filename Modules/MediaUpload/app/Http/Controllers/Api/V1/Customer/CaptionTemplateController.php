<?php

declare(strict_types=1);

namespace Modules\MediaUpload\Http\Controllers\Api\V1\Customer;

use Modules\Core\Http\Controllers\Api\BaseApiController;
use Modules\MediaUpload\Http\Requests\CreateCaptionTemplateRequest;
use Modules\MediaUpload\Http\Requests\UpdateCaptionTemplateRequest;
use Modules\MediaUpload\Actions\CreateCaptionTemplateAction;
use Modules\MediaUpload\Actions\UpdateCaptionTemplateAction;
use Modules\MediaUpload\Actions\DeleteCaptionTemplateAction;
use Modules\MediaUpload\DTOs\CaptionTemplateDTO;
use Modules\MediaUpload\Http\Resources\CaptionTemplateResource;
use Modules\MediaUpload\Models\CaptionTemplate;
use Illuminate\Http\JsonResponse;

class CaptionTemplateController extends BaseApiController
{
    public function __construct(
        private CreateCaptionTemplateAction $createAction,
        private UpdateCaptionTemplateAction $updateAction,
        private DeleteCaptionTemplateAction $deleteAction
    ) {}

    public function index(): JsonResponse
    {
        $this->authorize('viewAny', CaptionTemplate::class);
        $templates = CaptionTemplate::where('user_id', auth()->id())->orderBy('name')->get();
        return $this->success(CaptionTemplateResource::collection($templates), 'Caption templates retrieved');
    }

    public function store(CreateCaptionTemplateRequest $request): JsonResponse
    {
        $this->authorize('create', CaptionTemplate::class);
        $dto = CaptionTemplateDTO::fromArray($request->validated());
        $template = $this->createAction->execute($dto);
        return $this->success(new CaptionTemplateResource($template), 'Caption template created', 201);
    }

    public function update(UpdateCaptionTemplateRequest $request, int $id): JsonResponse
    {
        $template = CaptionTemplate::where('user_id', auth()->id())->findOrFail($id);
        $this->authorize('update', $template);
        $template = $this->updateAction->execute($template, $request->validated());
        return $this->success(new CaptionTemplateResource($template), 'Caption template updated');
    }

    public function destroy(int $id): JsonResponse
    {
        $template = CaptionTemplate::where('user_id', auth()->id())->findOrFail($id);
        $this->authorize('delete', $template);
        $this->deleteAction->execute($template);
        return $this->success(null, 'Caption template deleted');
    }
}
