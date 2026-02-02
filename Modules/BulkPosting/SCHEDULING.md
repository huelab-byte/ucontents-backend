# Bulk Posting Schedule

## How it works

With **Media Upload** and a folder (e.g. "video") that has **5 items**, and a **1 min** interval:

| Time   | Action                    |
|--------|---------------------------|
| 0 min  | You click **Start** → campaign goes running, `started_at` is set |
| 1 min  | First item is due → one job is dispatched → first post runs |
| 2 min  | Second item is due → second post runs     |
| 3 min  | Third item is due → third post runs       |
| 4 min  | Fourth item is due → fourth post runs     |
| 5 min  | Fifth item is due → fifth post runs       |

So **one post per interval** (here, one per minute). Each content item is posted in order when its turn is due.

## You must run BOTH processes

1. **Laravel Scheduler** – runs the command `bulk-posting:process-schedule` every minute.  
   That command finds running campaigns whose “next post” time is due and dispatches one job per campaign per run.

2. **Queue worker** – runs the dispatched jobs (actually posts to channels).

- If only the queue worker is running, no jobs are ever dispatched, so posts stay **pending**.
- If only the scheduler is running, jobs are dispatched but never run, so posts stay **scheduled** until the scheduler retries them (stuck “scheduled” items are redispatched after 2 minutes). You must also run the queue worker for posts to become **published**.

### Development

Run **two** processes (e.g. two terminals):

```bash
# Terminal 1: run the scheduler (every minute it runs bulk-posting:process-schedule)
php artisan schedule:work

# Terminal 2: run the queue worker (processes the post jobs)
php artisan queue:work
```

### Production

- **Cron** (run every minute):

  ```bash
  * * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
  ```

- **Queue worker**: run via Supervisor, systemd, or your hosting’s queue worker (e.g. `php artisan queue:work`).

### Manual test

To trigger the schedule once without waiting for the next minute:

```bash
php artisan bulk-posting:process-schedule
```

Then the queue worker will process any dispatched jobs.
