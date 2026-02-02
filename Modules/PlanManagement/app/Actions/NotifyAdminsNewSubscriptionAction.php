<?php

declare(strict_types=1);

namespace Modules\PlanManagement\Actions;

use Modules\NotificationManagement\Jobs\SendRealtimeNotificationJob;
use Modules\NotificationManagement\Models\Notification;
use Modules\NotificationManagement\Models\NotificationRecipient;
use Modules\PaymentGateway\Models\Subscription;
use Modules\UserManagement\Models\User;

class NotifyAdminsNewSubscriptionAction
{
    public function execute(Subscription $subscription): void
    {
        $user = $subscription->user;
        $planName = $subscription->name;

        $notification = Notification::create([
            'type' => 'subscription_created',
            'title' => 'New subscription',
            'body' => ($user ? $user->name : 'A customer') . ' subscribed to: ' . $planName,
            'severity' => 'info',
            'data' => [
                'subscription_id' => $subscription->id,
                'subscription_number' => $subscription->subscription_number,
                'user_id' => $subscription->user_id,
                'plan_name' => $planName,
            ],
        ]);

        $adminIds = User::whereHas('roles', fn ($q) => $q->whereIn('slug', ['super_admin', 'admin']))
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->toArray();

        foreach ($adminIds as $adminId) {
            NotificationRecipient::create([
                'notification_id' => $notification->id,
                'user_id' => $adminId,
            ]);
            SendRealtimeNotificationJob::dispatch($notification->id, $adminId);
        }
    }
}
