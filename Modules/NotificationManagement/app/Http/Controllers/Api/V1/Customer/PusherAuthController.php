<?php

declare(strict_types=1);

namespace Modules\NotificationManagement\Http\Controllers\Api\V1\Customer;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Modules\Core\Http\Controllers\Api\BaseApiController;
use Modules\NotificationManagement\Http\Requests\PusherAuthRequest;
use Modules\NotificationManagement\Services\PusherService;

class PusherAuthController extends BaseApiController
{
    public function auth(PusherAuthRequest $request, PusherService $pusher): JsonResponse
    {
        $validated = $request->validated();

        $user = $request->user();
        $expected = 'private-user.' . $user->id;

        Log::debug('Customer Pusher auth request', [
            'user_id' => $user->id,
            'channel_name' => $validated['channel_name'],
            'expected' => $expected,
        ]);

        if ($validated['channel_name'] !== $expected) {
            Log::warning('Customer Pusher auth denied - channel mismatch', [
                'user_id' => $user->id,
                'requested' => $validated['channel_name'],
                'expected' => $expected,
            ]);
            return $this->forbidden('You are not authorized to subscribe to this channel.');
        }

        $auth = $pusher->authorizePrivateChannel(
            socketId: $validated['socket_id'],
            channelName: $validated['channel_name'],
        );

        Log::debug('Customer Pusher auth success', [
            'user_id' => $user->id,
            'channel_name' => $validated['channel_name'],
        ]);

        return $this->success($auth, 'Channel authorized');
    }
}

