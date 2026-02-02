<?php

declare(strict_types=1);

namespace Modules\NotificationManagement\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\UserManagement\Models\User;

class Notification extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'type',
        'title',
        'body',
        'data',
        'severity',
        'created_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'data' => 'array',
        ];
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function recipients(): HasMany
    {
        return $this->hasMany(NotificationRecipient::class, 'notification_id');
    }

    /**
     * Get the URL for this notification based on its type.
     * Returns a relative path that the frontend can navigate to.
     */
    public function getUrl(): ?string
    {
        return match ($this->type) {
            'support_ticket_created',
            'support_ticket_replied',
            'support_ticket_status_changed',
            'support_ticket_assigned' => $this->getSupportTicketUrl(),
            'subscription_expiring' => '/settings/subscription',
            'announcement' => null,
            default => null,
        };
    }

    /**
     * Get the URL for support ticket notifications.
     */
    private function getSupportTicketUrl(): ?string
    {
        $ticketId = $this->data['ticket_id'] ?? null;
        if (!$ticketId) {
            return null;
        }

        // Return generic path - frontend will determine correct route based on user role
        return "/support/tickets/{$ticketId}";
    }
}

