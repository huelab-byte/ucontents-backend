<?php

declare(strict_types=1);

namespace Modules\NotificationManagement\Http\Controllers\Api\V1\Customer;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\Http\Controllers\Api\BaseApiController;
use Modules\NotificationManagement\Actions\ClearAllNotificationsAction;
use Modules\NotificationManagement\Actions\MarkAllNotificationsReadAction;
use Modules\NotificationManagement\Actions\MarkNotificationReadAction;
use Modules\NotificationManagement\Http\Requests\MarkNotificationReadRequest;
use Modules\NotificationManagement\Http\Resources\NotificationRecipientResource;
use Modules\NotificationManagement\Models\NotificationRecipient;
use Modules\NotificationManagement\Services\NotificationQueryService;

class NotificationController extends BaseApiController
{
    public function index(Request $request, NotificationQueryService $service): JsonResponse
    {
        $userId = (int) $request->user()->id;
        $perPage = (int) $request->integer('per_page', 15);

        $paginator = $service->listForUser($userId, $perPage);

        return $this->paginatedResource($paginator, NotificationRecipientResource::class, 'Notifications retrieved successfully');
    }

    public function unreadCount(Request $request, NotificationQueryService $service): JsonResponse
    {
        $userId = (int) $request->user()->id;

        return $this->success([
            'unread_count' => $service->unreadCountForUser($userId),
        ], 'Unread count retrieved successfully');
    }

    public function markRead(
        MarkNotificationReadRequest $request,
        NotificationRecipient $recipient,
        MarkNotificationReadAction $action
    ): JsonResponse {
        $this->authorize('update', $recipient);

        $updated = $action->execute($recipient);

        return $this->success(new NotificationRecipientResource($updated->loadMissing('notification')), 'Notification marked as read');
    }

    public function markAllRead(Request $request, MarkAllNotificationsReadAction $action): JsonResponse
    {
        $userId = (int) $request->user()->id;
        $markedCount = $action->execute($userId);

        return $this->success([
            'marked_count' => $markedCount,
        ], 'All notifications marked as read');
    }

    public function clearAll(Request $request, ClearAllNotificationsAction $action): JsonResponse
    {
        $userId = (int) $request->user()->id;
        $deletedCount = $action->execute($userId);

        return $this->success([
            'deleted_count' => $deletedCount,
        ], 'All notifications cleared');
    }
}

