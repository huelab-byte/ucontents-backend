<?php

declare(strict_types=1);

namespace Modules\NotificationManagement\Http\Controllers\Api\V1\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Modules\Core\Http\Controllers\Api\BaseApiController;
use Modules\NotificationManagement\Http\Requests\PusherAuthRequest;
use Modules\NotificationManagement\Services\PusherService;

class PusherAuthController extends BaseApiController
{
    /**
     * Authorize admin for private Pusher channel.
     * 
     * Note: Admin access is enforced by the 'admin' middleware on the route.
     */
    public function auth(PusherAuthRequest $request, PusherService $pusher): JsonResponse
    {
        $validated = $request->validated();
        $user = $request->user();

        Log::debug('Admin Pusher auth request', [
            'user_id' => $user->id,
            'channel_name' => $validated['channel_name'],
        ]);

        // Validate the requested channel is the admin channel
        if ($validated['channel_name'] !== 'private-admins') {
            Log::warning('Admin Pusher auth denied - wrong channel', [
                'user_id' => $user->id,
                'requested' => $validated['channel_name'],
            ]);
            return $this->forbidden('You are not authorized to subscribe to this channel.');
        }

        $auth = $pusher->authorizePrivateChannel(
            socketId: $validated['socket_id'],
            channelName: $validated['channel_name'],
        );

        Log::debug('Admin Pusher auth success', [
            'user_id' => $user->id,
            'channel_name' => $validated['channel_name'],
        ]);

        return $this->success($auth, 'Channel authorized');
    }
}

