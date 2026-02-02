<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class SyncMigrations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'migrate:sync 
                            {--dry-run : Show what would be synced without making changes}
                            {--force : Force sync without confirmation in production}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync migrations table with existing database tables. Marks migrations as run if their tables already exist.';

    /**
     * Mapping of migration patterns to their corresponding tables.
     * Key: regex pattern for migration filename
     * Value: table name(s) to check
     */
    private array $migrationTableMap = [
        '/create_(\w+)_table/' => '$1',
    ];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if (app()->environment('production') && !$this->option('force') && !$this->option('dry-run')) {
            if (!$this->confirm('You are in production. Are you sure you want to sync migrations?')) {
                $this->info('Operation cancelled.');
                return 0;
            }
        }

        $pendingMigrations = $this->getPendingMigrations();
        
        if (empty($pendingMigrations)) {
            $this->info('No pending migrations to sync.');
            return 0;
        }

        $this->info('Found ' . count($pendingMigrations) . ' pending migration(s).');
        $this->newLine();

        $toSync = [];

        foreach ($pendingMigrations as $migration) {
            $tableName = $this->extractTableName($migration);
            
            if ($tableName && Schema::hasTable($tableName)) {
                $toSync[] = [
                    'migration' => $migration,
                    'table' => $tableName,
                ];
                $this->line("  <fg=yellow>→</> {$migration} (table '{$tableName}' exists)");
            } else {
                $this->line("  <fg=gray>○</> {$migration} (table not found or not a create migration)");
            }
        }

        if (empty($toSync)) {
            $this->newLine();
            $this->info('No migrations need syncing. All pending migrations have tables that do not exist yet.');
            return 0;
        }

        $this->newLine();

        if ($this->option('dry-run')) {
            $this->warn('DRY RUN - Would sync ' . count($toSync) . ' migration(s):');
            foreach ($toSync as $item) {
                $this->line("  - {$item['migration']}");
            }
            return 0;
        }

        if (!$this->option('force') && !$this->confirm('Sync ' . count($toSync) . ' migration(s) to the migrations table?')) {
            $this->info('Operation cancelled.');
            return 0;
        }

        $batch = $this->getNextBatchNumber();
        $synced = 0;

        foreach ($toSync as $item) {
            try {
                DB::table('migrations')->insert([
                    'migration' => $item['migration'],
                    'batch' => $batch,
                ]);
                $synced++;
                $this->info("  <fg=green>✓</> Synced: {$item['migration']}");
            } catch (\Exception $e) {
                $this->error("  <fg=red>✗</> Failed: {$item['migration']} - {$e->getMessage()}");
            }
        }

        $this->newLine();
        $this->info("Successfully synced {$synced} migration(s) to batch {$batch}.");

        return 0;
    }

    /**
     * Get all pending migrations.
     */
    private function getPendingMigrations(): array
    {
        $ranMigrations = DB::table('migrations')->pluck('migration')->toArray();
        $allMigrations = $this->getAllMigrationFiles();

        return array_diff($allMigrations, $ranMigrations);
    }

    /**
     * Get all migration files from all locations.
     */
    private function getAllMigrationFiles(): array
    {
        $migrations = [];

        // Main database migrations
        $mainPath = database_path('migrations');
        if (File::isDirectory($mainPath)) {
            foreach (File::files($mainPath) as $file) {
                $migrations[] = pathinfo($file->getFilename(), PATHINFO_FILENAME);
            }
        }

        // Module migrations
        $modulesPath = base_path('Modules');
        if (File::isDirectory($modulesPath)) {
            foreach (File::directories($modulesPath) as $moduleDir) {
                $moduleMigrationsPath = $moduleDir . '/database/migrations';
                if (File::isDirectory($moduleMigrationsPath)) {
                    foreach (File::files($moduleMigrationsPath) as $file) {
                        $migrations[] = pathinfo($file->getFilename(), PATHINFO_FILENAME);
                    }
                }
            }
        }

        return $migrations;
    }

    /**
     * Extract table name from migration filename.
     */
    private function extractTableName(string $migration): ?string
    {
        // Pattern: YYYY_MM_DD_NNNNNN_create_tablename_table
        if (preg_match('/^\d{4}_\d{2}_\d{2}_\d+_create_(\w+)_table$/', $migration, $matches)) {
            return $matches[1];
        }

        // Pattern: YYYY_MM_DD_NNNNNN_add_*_to_tablename_table
        if (preg_match('/^\d{4}_\d{2}_\d{2}_\d+_add_\w+_to_(\w+)_table$/', $migration, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Get the next batch number.
     */
    private function getNextBatchNumber(): int
    {
        $lastBatch = DB::table('migrations')->max('batch');
        return ($lastBatch ?? 0) + 1;
    }
}
