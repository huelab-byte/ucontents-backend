<?php

declare(strict_types=1);

namespace Modules\EmailManagement\Http\Controllers\Api\V1\Admin;

use Illuminate\Http\JsonResponse;
use Modules\Core\Http\Controllers\Api\BaseApiController;
use Modules\EmailManagement\Actions\CreateEmailTemplateAction;
use Modules\EmailManagement\Actions\DeleteEmailTemplateAction;
use Modules\EmailManagement\Actions\UpdateEmailTemplateAction;
use Modules\EmailManagement\DTOs\EmailTemplateDTO;
use Modules\EmailManagement\Http\Requests\StoreEmailTemplateRequest;
use Modules\EmailManagement\Http\Requests\UpdateEmailTemplateRequest;
use Modules\EmailManagement\Http\Resources\EmailTemplateResource;
use Modules\EmailManagement\Models\EmailTemplate;

class EmailTemplateController extends BaseApiController
{
    public function __construct(
        private CreateEmailTemplateAction $createAction,
        private UpdateEmailTemplateAction $updateAction,
        private DeleteEmailTemplateAction $deleteAction
    ) {
    }

    /**
     * List all email templates
     */
    public function index(): JsonResponse
    {
        $this->authorize('viewAny', EmailTemplate::class);

        $templates = EmailTemplate::latest()->paginate(15);

        return $this->paginatedResource(
            $templates,
            EmailTemplateResource::class,
            'Email templates retrieved successfully'
        );
    }

    /**
     * Show a specific email template
     */
    public function show(EmailTemplate $emailTemplate): JsonResponse
    {
        $this->authorize('view', $emailTemplate);

        return $this->success(
            new EmailTemplateResource($emailTemplate),
            'Email template retrieved successfully'
        );
    }

    /**
     * Create a new email template
     */
    public function store(StoreEmailTemplateRequest $request): JsonResponse
    {
        $this->authorize('create', EmailTemplate::class);

        $dto = EmailTemplateDTO::fromArray($request->validated());
        $template = $this->createAction->execute($dto);

        return $this->created(
            new EmailTemplateResource($template),
            'Email template created successfully'
        );
    }

    /**
     * Update an existing email template
     */
    public function update(UpdateEmailTemplateRequest $request, EmailTemplate $emailTemplate): JsonResponse
    {
        $this->authorize('update', $emailTemplate);

        $dto = EmailTemplateDTO::fromArray($request->validated());
        $template = $this->updateAction->execute($emailTemplate, $dto);

        return $this->success(
            new EmailTemplateResource($template),
            'Email template updated successfully'
        );
    }

    /**
     * Delete an email template
     */
    public function destroy(EmailTemplate $emailTemplate): JsonResponse
    {
        $this->authorize('delete', $emailTemplate);

        $this->deleteAction->execute($emailTemplate);

        return $this->success(null, 'Email template deleted successfully');
    }
}
