<?php

declare(strict_types=1);

namespace Modules\NotificationManagement\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Modules\Core\Http\Controllers\Api\BaseApiController;
use Modules\GeneralSettings\Services\GeneralSettingsService;

class PusherConfigController extends BaseApiController
{
    /**
     * Get public Pusher configuration (key and cluster only - no secrets).
     * This is safe to expose to the frontend.
     */
    public function index(GeneralSettingsService $settingsService): JsonResponse
    {
        $enabled = $settingsService->get('notification.pusher.enabled', false);
        
        Log::debug('Pusher config request', [
            'enabled' => $enabled,
        ]);
        
        if (!$enabled) {
            return $this->success([
                'enabled' => false,
                'key' => null,
                'cluster' => null,
            ], 'Pusher configuration retrieved');
        }

        $key = $settingsService->get('notification.pusher.key')
            ?: config('notificationmanagement.pusher.key', env('PUSHER_APP_KEY'));
        
        $cluster = $settingsService->get('notification.pusher.cluster')
            ?: config('notificationmanagement.pusher.cluster', env('PUSHER_APP_CLUSTER', 'mt1'));

        Log::debug('Pusher config response', [
            'enabled' => true,
            'key' => $key ? substr($key, 0, 8) . '...' : null,
            'cluster' => $cluster,
        ]);

        return $this->success([
            'enabled' => (bool) $enabled,
            'key' => $key,
            'cluster' => $cluster,
        ], 'Pusher configuration retrieved');
    }
}
