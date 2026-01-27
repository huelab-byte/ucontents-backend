<?php

declare(strict_types=1);

namespace Modules\EmailManagement\Http\Controllers\Api\V1\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\Http\Controllers\Api\BaseApiController;
use Modules\EmailManagement\Actions\CreateSmtpConfigurationAction;
use Modules\EmailManagement\Actions\DeleteSmtpConfigurationAction;
use Modules\EmailManagement\Actions\SetDefaultSmtpConfigurationAction;
use Modules\EmailManagement\Actions\UpdateSmtpConfigurationAction;
use Modules\EmailManagement\DTOs\SmtpConfigurationDTO;
use Modules\EmailManagement\Http\Requests\StoreSmtpConfigurationRequest;
use Modules\EmailManagement\Http\Requests\UpdateSmtpConfigurationRequest;
use Modules\EmailManagement\Http\Resources\SmtpConfigurationResource;
use Modules\EmailManagement\Models\SmtpConfiguration;

class SmtpConfigurationController extends BaseApiController
{
    public function __construct(
        private CreateSmtpConfigurationAction $createAction,
        private UpdateSmtpConfigurationAction $updateAction,
        private DeleteSmtpConfigurationAction $deleteAction,
        private SetDefaultSmtpConfigurationAction $setDefaultAction
    ) {
    }

    /**
     * List all SMTP configurations
     */
    public function index(): JsonResponse
    {
        $this->authorize('viewAny', SmtpConfiguration::class);

        $configs = SmtpConfiguration::latest()->paginate(15);

        return $this->paginatedResource(
            $configs,
            SmtpConfigurationResource::class,
            'SMTP configurations retrieved successfully'
        );
    }

    /**
     * Show a specific SMTP configuration
     */
    public function show(SmtpConfiguration $smtpConfiguration): JsonResponse
    {
        $this->authorize('view', $smtpConfiguration);

        return $this->success(
            new SmtpConfigurationResource($smtpConfiguration),
            'SMTP configuration retrieved successfully'
        );
    }

    /**
     * Create a new SMTP configuration
     */
    public function store(StoreSmtpConfigurationRequest $request): JsonResponse
    {
        $this->authorize('create', SmtpConfiguration::class);

        $dto = SmtpConfigurationDTO::fromArray($request->validated());
        $config = $this->createAction->execute($dto);

        return $this->created(
            new SmtpConfigurationResource($config),
            'SMTP configuration created successfully'
        );
    }

    /**
     * Update an existing SMTP configuration
     */
    public function update(UpdateSmtpConfigurationRequest $request, SmtpConfiguration $smtpConfiguration): JsonResponse
    {
        $this->authorize('update', $smtpConfiguration);

        $validated = $request->validated();
        
        // Don't update password if not provided
        if (empty($validated['password'])) {
            unset($validated['password']);
        }

        $dto = SmtpConfigurationDTO::fromArray($validated);
        $config = $this->updateAction->execute($smtpConfiguration, $dto);

        return $this->success(
            new SmtpConfigurationResource($config),
            'SMTP configuration updated successfully'
        );
    }

    /**
     * Delete an SMTP configuration
     */
    public function destroy(SmtpConfiguration $smtpConfiguration): JsonResponse
    {
        $this->authorize('delete', $smtpConfiguration);

        $this->deleteAction->execute($smtpConfiguration);

        return $this->success(null, 'SMTP configuration deleted successfully');
    }

    /**
     * Set as default SMTP configuration
     */
    public function setDefault(SmtpConfiguration $smtpConfiguration): JsonResponse
    {
        $this->authorize('setDefault', $smtpConfiguration);

        $config = $this->setDefaultAction->execute($smtpConfiguration);

        return $this->success(
            new SmtpConfigurationResource($config),
            'SMTP configuration set as default successfully'
        );
    }
}
