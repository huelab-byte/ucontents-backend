# Run the queue worker in a loop so it restarts automatically if it exits
# (e.g. lost DB connection, memory limit). Press Ctrl+C once to stop.
# Usage: .\queue-work-loop.ps1   or   pwsh -File queue-work-loop.ps1

$ErrorActionPreference = "Continue"
Write-Host "Queue worker loop: will restart automatically on exit. Press Ctrl+C to stop." -ForegroundColor Cyan
while ($true) {
    php artisan queue:work --tries=3 --timeout=600 --memory=256
    $exitCode = $LASTEXITCODE
    Write-Host "Worker exited with code $exitCode. Restarting in 2 seconds..." -ForegroundColor Yellow
    Start-Sleep -Seconds 2
}
