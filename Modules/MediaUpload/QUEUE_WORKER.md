# Media Upload Queue Worker

## Why the worker stops even without `--stop-when-empty`

Laravel’s worker can exit on its own for several reasons:

1. **Lost connection** – If the queue uses the **database** driver, or a job triggers DB/Redis I/O and the connection has dropped (e.g. MySQL “server has gone away”, “Connection lost”, idle timeout), Laravel treats that as a lost connection and **stops the worker** so it can be restarted with a fresh connection.
2. **Memory limit** – Default is `--memory=128`. One heavy job can exceed it and the worker exits (exit code 12).
3. **`--stop-when-empty`** – If you pass this, the worker stops when the queue is empty.
4. **`--max-time` / `--max-jobs`** – If set, the worker stops after that time or job count.

So even with `php artisan queue:work` (no `--stop-when-empty`), the worker can still stop after failed jobs if a **lost DB/Redis connection** occurs during failure handling.

### Keep the worker running locally: use a restart loop

Run the worker in a loop so it restarts whenever it exits (for any reason):

**PowerShell (Windows):**
```powershell
# From project root (backend folder)
while ($true) { php artisan queue:work --tries=3 --timeout=600 --memory=256; Start-Sleep -Seconds 2 }
```

**Bash (Linux/Mac):**
```bash
# From project root (backend folder)
while true; do php artisan queue:work --tries=3 --timeout=600 --memory=256; sleep 2; done
```

Press **Ctrl+C** once to stop the loop.

### Reduce “lost connection” stops

- **Prefer Redis for the queue** – Set `QUEUE_CONNECTION=redis` in `.env`. Redis is better for long‑running workers than the database driver.
- **If you keep the database queue** – Increase MySQL `wait_timeout` / `interactive_timeout` so idle connections don’t drop as quickly (e.g. in `my.ini` or MySQL config).

For production, use a process manager (e.g. systemd) so the worker restarts if it crashes; see `deployment/laravel-queue-worker.service`.

### Multiple workers for faster processing

To process several uploads in parallel (e.g. multiple folders or many files), run **multiple worker processes**. Each job is handled by one worker; N workers = up to N jobs at once.

- **Local:** Open 2–3 terminals, in each run:
  ```bash
  php artisan queue:work redis --queue=default,media-uploads --tries=3 --timeout=600
  ```
- **Production:** Use the systemd template and enable 2–4 instances; see [Deployment – Multiple workers](docs/DEPLOYMENT.md#8-queue-worker-systemd).

---

## Why ProcessMediaUploadJob fails

Common causes:

1. **"No active API keys found" / "No AI API key available for content generation"**  
   The job uses the **AiIntegration** module to generate headings and captions. You must configure at least one AI provider (e.g. OpenAI) with an API key in **Admin → AI Integration** (or your app’s AI settings). Without a valid key, content generation fails and the job is marked failed.

2. **"Temporary file not found"**  
   The file was not stored correctly before the job ran, or the path is wrong. Ensure chunked upload completed and the worker runs on the same machine (and same `storage` path) as the web app.

3. **FFmpeg / video processing errors**  
   Install FFmpeg and ensure it is on the system `PATH` for the user running the queue worker.

---

## Inspecting failed jobs

- **In the app**: Media Upload queue list shows status and error message per item.
- **Laravel failed_jobs table** (if the job was retried and then failed):
  ```bash
  php artisan queue:failed
  php artisan queue:retry all   # optional: retry all failed jobs
  ```

## Recommended local command

```bash
php artisan queue:work --tries=3 --timeout=600
```

This keeps the worker running, allows up to 3 attempts per job (for transient errors), and gives each job up to 600 seconds. Fix the root cause (e.g. add an AI API key) so new jobs succeed.
