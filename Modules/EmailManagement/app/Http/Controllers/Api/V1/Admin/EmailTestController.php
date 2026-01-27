<?php

declare(strict_types=1);

namespace Modules\EmailManagement\Http\Controllers\Api\V1\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use Modules\Core\Http\Controllers\Api\BaseApiController;
use Modules\EmailManagement\Actions\SendEmailAction;
use Modules\EmailManagement\DTOs\SendEmailDTO;
use Modules\EmailManagement\Http\Requests\SendTestEmailRequest;
use Modules\EmailManagement\Http\Resources\EmailLogResource;

class EmailTestController extends BaseApiController
{
    public function __construct(
        private SendEmailAction $sendEmailAction
    ) {
    }

    /**
     * Send a test email
     */
    public function sendTest(SendTestEmailRequest $request): JsonResponse
    {
        Gate::authorize('send_test_email');

        try {
            $validated = $request->validated();

            $emailService = app(\Modules\EmailManagement\Services\EmailService::class);

            // If subject and body are provided, send custom test email
            if (isset($validated['subject']) && isset($validated['body'])) {
                $dto = new SendEmailDTO(
                    to: $validated['to'],
                    cc: null,
                    bcc: null,
                    subject: $validated['subject'],
                    body: $validated['body'],
                    templateId: null,
                    templateVariables: null,
                    smtpConfigurationId: $validated['smtp_configuration_id'] ?? null,
                    useQueue: false, // Send immediately for test
                    metadata: null,
                );

                $emailLog = $this->sendEmailAction->execute($dto);

                return $this->success(
                    new EmailLogResource($emailLog),
                    'Test email sent successfully'
                );
            } else {
                // Test SMTP configuration
                $result = $emailService->testSmtpConfiguration(
                    $validated['smtp_configuration_id'] ?? null,
                    $validated['to']
                );

                if ($result['success']) {
                    return $this->success($result, $result['message']);
                } else {
                    return $this->error($result['message'], 400);
                }
            }
        } catch (\Exception $e) {
            return $this->handleException($e, 'Failed to send test email.');
        }
    }
}
