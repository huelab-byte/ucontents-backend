<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * Destroy all user sessions so users must log in again.
 * Use after deployment when permissions or roles have changed.
 */
class DestroyAllSessions extends Command
{
    protected $signature = 'sessions:destroy
                            {--tokens-only : Only revoke Sanctum tokens (skip web sessions)}
                            {--dry-run : Show what would be done without doing it}';

    protected $description = 'Revoke all Sanctum tokens and clear sessions so users must log in again (use after deployment)';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $tokensOnly = $this->option('tokens-only');

        if ($dryRun) {
            $this->warn('Dry run – no changes will be made.');
        }

        // 1. Revoke all Sanctum tokens (API Bearer tokens – primary auth for SPA)
        if (Schema::hasTable('personal_access_tokens')) {
            $tokensCount = PersonalAccessToken::count();
            if ($tokensCount > 0) {
                if (!$dryRun) {
                    PersonalAccessToken::query()->delete();
                }
                $this->info(($dryRun ? '[DRY RUN] Would revoke ' : 'Revoked ') . $tokensCount . ' Sanctum token(s).');
            } else {
                $this->line('No Sanctum tokens to revoke.');
            }
        } else {
            $this->warn('Table personal_access_tokens not found. Skipping.');
        }

        if ($tokensOnly) {
            $this->info('Done (tokens only).');
            return Command::SUCCESS;
        }

        // 2. Clear web sessions based on driver
        $driver = config('session.driver');
        $this->line('Session driver: ' . $driver);

        switch ($driver) {
            case 'database':
                $table = config('session.table', 'sessions');
                if (Schema::hasTable($table)) {
                    $sessionsCount = DB::table($table)->count();
                    if (!$dryRun && $sessionsCount > 0) {
                        DB::table($table)->truncate();
                    }
                    $this->info(($dryRun ? '[DRY RUN] Would clear ' : 'Cleared ') . $sessionsCount . ' session(s) from database.');
                }
                break;

            case 'redis':
                // Only flush if session uses a dedicated store (avoids clearing app cache)
                $sessionStore = config('session.store');
                if ($sessionStore && $sessionStore !== config('cache.default')) {
                    try {
                        $store = app('cache')->store($sessionStore);
                        if (method_exists($store, 'flush')) {
                            if (!$dryRun) {
                                $store->flush();
                            }
                            $this->info($dryRun ? '[DRY RUN] Would clear Redis session store.' : 'Cleared Redis session store.');
                        }
                    } catch (\Throwable $e) {
                        $this->warn('Could not clear Redis sessions: ' . $e->getMessage());
                    }
                } else {
                    $this->line('Redis sessions share cache store – skipped to avoid clearing cache. Sanctum tokens were revoked.');
                }
                break;

            case 'file':
                $path = storage_path('framework/sessions');
                if (is_dir($path)) {
                    $files = File::glob($path . '/*');
                    $count = count(array_filter($files, 'is_file'));
                    if (!$dryRun && $count > 0) {
                        File::cleanDirectory($path);
                    }
                    $this->info(($dryRun ? '[DRY RUN] Would clear ' : 'Cleared ') . $count . ' file session(s).');
                }
                break;

            default:
                $this->line('Session driver "' . $driver . '" – manual clear not implemented. Sanctum tokens were revoked.');
        }

        $this->newLine();
        $this->info('All users must log in again to access the application.');

        return Command::SUCCESS;
    }
}
