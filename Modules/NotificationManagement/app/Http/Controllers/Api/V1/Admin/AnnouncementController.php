<?php

declare(strict_types=1);

namespace Modules\NotificationManagement\Http\Controllers\Api\V1\Admin;

use Illuminate\Http\JsonResponse;
use Modules\Core\Http\Controllers\Api\BaseApiController;
use Modules\NotificationManagement\Actions\CreateAnnouncementAction;
use Modules\NotificationManagement\DTOs\CreateAnnouncementDTO;
use Modules\NotificationManagement\Http\Requests\StoreAnnouncementRequest;
use Modules\NotificationManagement\Http\Requests\ListAnnouncementsRequest;
use Modules\NotificationManagement\Http\Resources\NotificationResource;
use Modules\NotificationManagement\Jobs\DeliverAnnouncementEmailJob;
use Modules\NotificationManagement\Jobs\SendRealtimeAdminsAnnouncementJob;
use Modules\NotificationManagement\Jobs\SendRealtimeNotificationJob;
use Modules\NotificationManagement\Models\Notification;

class AnnouncementController extends BaseApiController
{
    public function index(ListAnnouncementsRequest $request): JsonResponse
    {
        $this->authorize('viewAny', Notification::class);

        $perPage = (int) $request->integer('per_page', 15);

        $paginator = Notification::query()
            ->where('type', 'announcement')
            ->orderByDesc('created_at')
            ->paginate($perPage);

        return $this->paginatedResource($paginator, NotificationResource::class, 'Announcements retrieved successfully');
    }

    public function store(StoreAnnouncementRequest $request, CreateAnnouncementAction $action): JsonResponse
    {
        $this->authorize('create', Notification::class);

        $dto = CreateAnnouncementDTO::fromArray($request->validated());

        $result = $action->execute($dto, $request->user()?->id);

        /** @var Notification $notification */
        $notification = $result['notification'];

        // Email delivery (async)
        if ($dto->sendEmail) {
            foreach ($result['recipient_user_ids'] as $userId) {
                DeliverAnnouncementEmailJob::dispatch(
                    notificationId: $notification->id,
                    userId: (int) $userId
                );
            }
        }

        // Realtime (async)
        foreach ($result['recipient_user_ids'] as $userId) {
            SendRealtimeNotificationJob::dispatch(
                notificationId: $notification->id,
                userId: (int) $userId
            );
        }

        if ($dto->audience === 'all_admins') {
            SendRealtimeAdminsAnnouncementJob::dispatch(notificationId: $notification->id);
        }

        return $this->created([
            'notification' => new NotificationResource($notification),
            'recipients_created' => $result['recipients_created'],
        ], 'Announcement created');
    }
}

