<?php

declare(strict_types=1);

namespace Modules\AiIntegration\Http\Controllers\Api\V1\Admin;

use Illuminate\Http\JsonResponse;
use Modules\AiIntegration\Actions\CreatePromptTemplateAction;
use Modules\AiIntegration\Actions\DeletePromptTemplateAction;
use Modules\AiIntegration\Actions\UpdatePromptTemplateAction;
use Modules\AiIntegration\DTOs\CreatePromptTemplateDTO;
use Modules\AiIntegration\DTOs\UpdatePromptTemplateDTO;
use Modules\AiIntegration\Http\Requests\StorePromptTemplateRequest;
use Modules\AiIntegration\Http\Requests\UpdatePromptTemplateRequest;
use Modules\AiIntegration\Http\Requests\ListAiPromptTemplatesRequest;
use Modules\AiIntegration\Http\Resources\AiPromptTemplateResource;
use Modules\AiIntegration\Models\AiPromptTemplate;
use Modules\Core\Http\Controllers\Api\BaseApiController;

/**
 * Admin API Controller for managing prompt templates
 */
class AiPromptTemplateController extends BaseApiController
{
    public function __construct(
        private CreatePromptTemplateAction $createTemplateAction,
        private UpdatePromptTemplateAction $updateTemplateAction,
        private DeletePromptTemplateAction $deleteTemplateAction
    ) {
    }

    /**
     * List all prompt templates
     */
    public function index(ListAiPromptTemplatesRequest $request): JsonResponse
    {
        $this->authorize('viewAny', AiPromptTemplate::class);

        $query = AiPromptTemplate::query();

        // Filter by category
        if ($request->has('category')) {
            $query->where('category', $request->input('category'));
        }

        // Filter by provider
        if ($request->has('provider_slug')) {
            $query->where('provider_slug', $request->input('provider_slug'));
        }

        // Filter by active status
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $templates = $query->orderBy('category')
            ->orderBy('name')
            ->paginate($request->input('per_page', 15));

        return $this->paginatedResource(
            $templates,
            AiPromptTemplateResource::class,
            'Prompt templates retrieved successfully'
        );
    }

    /**
     * Show a specific template
     */
    public function show(AiPromptTemplate $promptTemplate): JsonResponse
    {
        $this->authorize('view', $promptTemplate);

        return $this->success(
            new AiPromptTemplateResource($promptTemplate),
            'Prompt template retrieved successfully'
        );
    }

    /**
     * Create a new template
     */
    public function store(StorePromptTemplateRequest $request): JsonResponse
    {
        $this->authorize('create', AiPromptTemplate::class);

        $dto = CreatePromptTemplateDTO::fromArray(
            array_merge($request->validated(), [
                'created_by' => $request->user()?->id,
            ])
        );

        $template = $this->createTemplateAction->execute($dto);

        return $this->created(
            new AiPromptTemplateResource($template),
            'Prompt template created successfully'
        );
    }

    /**
     * Update a template
     */
    public function update(UpdatePromptTemplateRequest $request, AiPromptTemplate $promptTemplate): JsonResponse
    {
        $this->authorize('update', $promptTemplate);

        $dto = UpdatePromptTemplateDTO::fromArray($request->validated());
        $template = $this->updateTemplateAction->execute($promptTemplate, $dto);

        return $this->success(
            new AiPromptTemplateResource($template),
            'Prompt template updated successfully'
        );
    }

    /**
     * Delete a template
     */
    public function destroy(AiPromptTemplate $promptTemplate): JsonResponse
    {
        $this->authorize('delete', $promptTemplate);

        try {
            $this->deleteTemplateAction->execute($promptTemplate);
            return $this->success(null, 'Prompt template deleted successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 403);
        }
    }
}
