<?php

declare(strict_types=1);

namespace Modules\NotificationManagement\Http\Controllers\Api\V1\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use Modules\Core\Http\Controllers\Api\BaseApiController;
use Modules\GeneralSettings\Services\GeneralSettingsService;
use Modules\NotificationManagement\Http\Requests\UpdateNotificationSettingsRequest;

class NotificationSettingsController extends BaseApiController
{
    public function index(GeneralSettingsService $settingsService): JsonResponse
    {
        Gate::authorize('manage_notification_settings');

        $pusherSettings = [
            'pusher_app_id' => $settingsService->get('notification.pusher.app_id', ''),
            'pusher_key' => $settingsService->get('notification.pusher.key', ''),
            'pusher_secret' => $settingsService->get('notification.pusher.secret', ''),
            'pusher_cluster' => $settingsService->get('notification.pusher.cluster', 'mt1'),
            'pusher_enabled' => $settingsService->get('notification.pusher.enabled', false),
        ];

        return $this->success($pusherSettings, 'Notification settings retrieved successfully');
    }

    public function update(UpdateNotificationSettingsRequest $request, GeneralSettingsService $settingsService): JsonResponse
    {
        Gate::authorize('manage_notification_settings');

        $validated = $request->validated();

        // Save to database using GeneralSettings service
        if (isset($validated['pusher_app_id'])) {
            $settingsService->set('notification.pusher.app_id', $validated['pusher_app_id']);
        }
        if (isset($validated['pusher_key'])) {
            $settingsService->set('notification.pusher.key', $validated['pusher_key']);
        }
        if (isset($validated['pusher_secret'])) {
            $settingsService->set('notification.pusher.secret', $validated['pusher_secret']);
        }
        if (isset($validated['pusher_cluster'])) {
            $settingsService->set('notification.pusher.cluster', $validated['pusher_cluster']);
        }
        if (isset($validated['pusher_enabled'])) {
            $settingsService->set('notification.pusher.enabled', $validated['pusher_enabled'], 'boolean');
        }

        // Clear cache
        \Modules\GeneralSettings\Models\GeneralSetting::clearCache();

        // Return updated settings
        $pusherSettings = [
            'pusher_app_id' => $settingsService->get('notification.pusher.app_id', ''),
            'pusher_key' => $settingsService->get('notification.pusher.key', ''),
            'pusher_secret' => $settingsService->get('notification.pusher.secret', ''),
            'pusher_cluster' => $settingsService->get('notification.pusher.cluster', 'mt1'),
            'pusher_enabled' => $settingsService->get('notification.pusher.enabled', false),
        ];

        return $this->success($pusherSettings, 'Notification settings updated successfully');
    }
}
