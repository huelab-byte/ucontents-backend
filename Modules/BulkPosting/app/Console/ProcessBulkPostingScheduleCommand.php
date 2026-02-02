<?php

declare(strict_types=1);

namespace Modules\BulkPosting\Console;

use Illuminate\Console\Command;
use Modules\BulkPosting\Services\ScheduleService;

class ProcessBulkPostingScheduleCommand extends Command
{
    protected $signature = 'bulk-posting:process-schedule';
    protected $description = 'Process due bulk posting campaigns and dispatch post jobs (run every minute via schedule:work or cron)';

    public function handle(ScheduleService $scheduleService): int
    {
        $dispatched = $scheduleService->processDueCampaigns();

        if ($dispatched > 0) {
            $this->info("Dispatched {$dispatched} post job(s).");
        }

        return self::SUCCESS;
    }
}
