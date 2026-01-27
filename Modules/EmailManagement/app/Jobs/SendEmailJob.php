<?php

declare(strict_types=1);

namespace Modules\EmailManagement\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\EmailManagement\Models\EmailLog;
use Modules\EmailManagement\Services\EmailService;

/**
 * Job to send email asynchronously
 */
class SendEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public int $backoff = 60;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $emailLogId
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(EmailService $emailService): void
    {
        $emailLog = EmailLog::findOrFail($this->emailLogId);

        \Log::info('SendEmailJob: Processing email', [
            'email_log_id' => $emailLog->id,
            'to' => $emailLog->to,
            'status' => $emailLog->status,
            'subject' => $emailLog->subject,
        ]);

        if ($emailLog->status === 'sent') {
            \Log::info('SendEmailJob: Email already sent, skipping', [
                'email_log_id' => $emailLog->id,
            ]);
            return; // Already sent
        }

        try {
            $emailService->sendEmail($emailLog);
            \Log::info('SendEmailJob: Email sent successfully', [
                'email_log_id' => $emailLog->id,
                'to' => $emailLog->to,
            ]);
        } catch (\Exception $e) {
            \Log::error('SendEmailJob: Failed to send email', [
                'email_log_id' => $emailLog->id,
                'to' => $emailLog->to,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e; // Re-throw to trigger job failure handling
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        $emailLog = EmailLog::find($this->emailLogId);
        
        if ($emailLog) {
            $emailLog->markAsFailed($exception->getMessage());
        }
    }
}
