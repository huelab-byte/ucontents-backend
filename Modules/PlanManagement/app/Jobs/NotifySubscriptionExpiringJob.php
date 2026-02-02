<?php

declare(strict_types=1);

namespace Modules\PlanManagement\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\NotificationManagement\Jobs\SendRealtimeNotificationJob;
use Modules\NotificationManagement\Models\Notification;
use Modules\NotificationManagement\Models\NotificationRecipient;
use Modules\PaymentGateway\Models\Subscription;

class NotifySubscriptionExpiringJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct()
    {
    }

    public function handle(): void
    {
        $days = config('planmanagement.subscription_expiring_days', 7);
        $threshold = now()->addDays($days);

        $subscriptions = Subscription::where('status', 'active')
            ->where(function ($q) use ($threshold) {
                $q->whereNotNull('end_date')->where('end_date', '<=', $threshold)
                    ->orWhereNotNull('next_billing_date')->where('next_billing_date', '<=', $threshold);
            })
            ->with('user')
            ->get();

        foreach ($subscriptions as $subscription) {
            if ($this->alreadyNotifiedRecently($subscription->id, $days)) {
                continue;
            }
            $this->notifyUser($subscription, $days);
        }
    }

    private function alreadyNotifiedRecently(int $subscriptionId, int $days): bool
    {
        return Notification::where('type', 'subscription_expiring')
            ->where('data->subscription_id', $subscriptionId)
            ->where('created_at', '>=', now()->subDays($days))
            ->exists();
    }

    private function notifyUser(Subscription $subscription, int $days): void
    {
        $userId = $subscription->user_id;
        $planName = $subscription->name;
        $date = $subscription->end_date ?? $subscription->next_billing_date;
        $dateStr = $date ? $date->format('Y-m-d') : 'soon';

        $notification = Notification::create([
            'type' => 'subscription_expiring',
            'title' => 'Subscription expiring soon',
            'body' => "Your subscription to \"{$planName}\" expires or renews on {$dateStr} (in {$days} days). Please renew to avoid interruption.",
            'severity' => 'warning',
            'data' => [
                'subscription_id' => $subscription->id,
                'subscription_number' => $subscription->subscription_number,
                'user_id' => $userId,
                'plan_name' => $planName,
                'expiry_date' => $dateStr,
                'days_until' => $days,
            ],
        ]);

        NotificationRecipient::create([
            'notification_id' => $notification->id,
            'user_id' => $userId,
        ]);
        SendRealtimeNotificationJob::dispatch($notification->id, $userId);

        Log::info('Subscription expiring notification sent', [
            'subscription_id' => $subscription->id,
            'user_id' => $userId,
        ]);
    }
}
