<?php

declare(strict_types=1);

namespace Modules\EmailManagement\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Email Log Model
 */
class EmailLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'smtp_configuration_id',
        'email_template_id',
        'to',
        'cc',
        'bcc',
        'subject',
        'body',
        'status',
        'error_message',
        'sent_at',
        'metadata',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'metadata' => 'array',
    ];

    /**
     * Get the SMTP configuration used for this email
     */
    public function smtpConfiguration(): BelongsTo
    {
        return $this->belongsTo(SmtpConfiguration::class);
    }

    /**
     * Get the email template used for this email
     */
    public function emailTemplate(): BelongsTo
    {
        return $this->belongsTo(EmailTemplate::class);
    }

    /**
     * Mark email as sent
     */
    public function markAsSent(): void
    {
        $this->update([
            'status' => 'sent',
            'sent_at' => now(),
        ]);
    }

    /**
     * Mark email as failed
     */
    public function markAsFailed(string $errorMessage): void
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $errorMessage,
        ]);
    }
}
