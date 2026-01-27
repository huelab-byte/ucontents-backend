<?php

declare(strict_types=1);

namespace Modules\AiIntegration\Http\Controllers\Api\V1\Customer;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\AiIntegration\Http\Requests\RenderPromptTemplateRequest;
use Modules\AiIntegration\Http\Resources\AiPromptTemplateResource;
use Modules\AiIntegration\Models\AiPromptTemplate;
use Modules\Core\Http\Controllers\Api\BaseApiController;

/**
 * Customer API Controller for viewing prompt templates
 */
class AiPromptTemplateController extends BaseApiController
{
    /**
     * List active prompt templates
     */
    public function index(Request $request): JsonResponse
    {
        $query = AiPromptTemplate::where('is_active', true);

        // Filter by category
        if ($request->has('category')) {
            $query->where('category', $request->input('category'));
        }

        // Filter by provider
        if ($request->has('provider_slug')) {
            $query->where('provider_slug', $request->input('provider_slug'));
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
        if (!$promptTemplate->is_active) {
            return $this->notFound('Template not found');
        }

        return $this->success(
            new AiPromptTemplateResource($promptTemplate),
            'Prompt template retrieved successfully'
        );
    }

    /**
     * Render a template with variables
     */
    public function render(RenderPromptTemplateRequest $request, AiPromptTemplate $promptTemplate): JsonResponse
    {
        if (!$promptTemplate->is_active) {
            return $this->notFound('Template not found');
        }

        $rendered = $promptTemplate->render($request->validated()['variables']);

        return $this->success([
            'rendered_template' => $rendered,
            'template' => new AiPromptTemplateResource($promptTemplate),
        ], 'Template rendered successfully');
    }
}
