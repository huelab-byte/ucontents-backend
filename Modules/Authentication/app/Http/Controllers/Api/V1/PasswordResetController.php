<?php

declare(strict_types=1);

namespace Modules\Authentication\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Modules\Authentication\Actions\PasswordResetAction;
use Modules\Authentication\Actions\PasswordResetRequestAction;
use Modules\Authentication\DTOs\PasswordResetDTO;
use Modules\Authentication\DTOs\PasswordResetRequestDTO;
use Modules\Authentication\Exceptions\InvalidPasswordResetTokenException;
use Modules\Authentication\Exceptions\PasswordResetException;
use Modules\Authentication\Exceptions\PasswordResetThrottledException;
use Modules\Authentication\Exceptions\UserNotFoundException;
use Modules\Authentication\Http\Requests\PasswordResetRequest;
use Modules\Authentication\Http\Requests\PasswordResetRequestRequest;
use Modules\Authentication\Services\AuthenticationSettingsService;
use Modules\Core\Http\Controllers\Api\BaseApiController;

/**
 * Password Reset Controller
 */
class PasswordResetController extends BaseApiController
{
    public function __construct(
        private PasswordResetRequestAction $passwordResetRequestAction,
        private PasswordResetAction $passwordResetAction
    ) {
    }

    /**
     * Request password reset
     */
    public function request(PasswordResetRequestRequest $request): JsonResponse
    {
        try {
            // Check if password reset is enabled
            $settingsService = app(AuthenticationSettingsService::class);
            $passwordResetEnabled = $settingsService->get('features.password_reset.enabled', false);
            
            if (!$passwordResetEnabled) {
                return $this->error('Password reset is currently disabled.', 403);
            }

            $dto = PasswordResetRequestDTO::fromArray($request->validated());
            $this->passwordResetRequestAction->execute($dto);

            return $this->success([
                'message' => 'Password reset link sent to your email',
            ], 'Password reset link sent successfully');
        } catch (UserNotFoundException $e) {
            return $this->error($e->getMessage(), 404);
        } catch (PasswordResetThrottledException $e) {
            return $this->tooManyRequests($e->getMessage());
        } catch (PasswordResetException $e) {
            return $this->error($e->getMessage(), $e->getCode());
        } catch (\Exception $e) {
            return $this->handleException($e, 'Failed to send password reset link.');
        }
    }

    /**
     * Reset password
     */
    public function reset(PasswordResetRequest $request): JsonResponse
    {
        try {
            // Check if password reset is enabled
            $settingsService = app(AuthenticationSettingsService::class);
            $passwordResetEnabled = $settingsService->get('features.password_reset.enabled', false);
            
            if (!$passwordResetEnabled) {
                return $this->error('Password reset is currently disabled.', 403);
            }

            $dto = PasswordResetDTO::fromArray($request->validated());
            $this->passwordResetAction->execute($dto);

            return $this->success(null, 'Password reset successfully');
        } catch (InvalidPasswordResetTokenException $e) {
            return $this->error($e->getMessage(), 400);
        } catch (UserNotFoundException $e) {
            return $this->error($e->getMessage(), 404);
        } catch (PasswordResetThrottledException $e) {
            return $this->tooManyRequests($e->getMessage());
        } catch (PasswordResetException $e) {
            return $this->error($e->getMessage(), $e->getCode());
        } catch (\Exception $e) {
            return $this->handleException($e, 'Failed to reset password.');
        }
    }
}
