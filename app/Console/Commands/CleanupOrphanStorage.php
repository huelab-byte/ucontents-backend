<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Modules\StorageManagement\Factories\StorageDriverFactory;
use Modules\MediaUpload\Models\MediaUploadFolder;

class CleanupOrphanStorage extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'storage:cleanup-orphans 
                            {--dry-run : Show what would be deleted without making changes}
                            {--force : Force cleanup without confirmation in production}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Find and remove orphan storage folders (folders in storage that have no corresponding database record)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if (app()->environment('production') && !$this->option('force') && !$this->option('dry-run')) {
            if (!$this->confirm('You are in production. Are you sure you want to cleanup orphan storage?')) {
                $this->info('Operation cancelled.');
                return 0;
            }
        }

        $this->info('Scanning for orphan storage folders...');
        $this->newLine();

        try {
            $driver = StorageDriverFactory::make();
        } catch (\Exception $e) {
            $this->error('Failed to initialize storage driver: ' . $e->getMessage());
            return 1;
        }

        // Get all folders in media-upload directory
        $storageFolders = $driver->listFiles('media-upload/', false);
        
        // Extract folder names (remove the media-upload/ prefix)
        $folderPaths = [];
        foreach ($storageFolders as $path) {
            // Skip files, only process directories (paths ending with /)
            // or paths that look like folder-{id} pattern
            if (preg_match('/^media-upload\/(.+?)\/?$/', $path, $matches)) {
                $folderPaths[] = $matches[1];
            }
        }

        // Also get all unique top-level folders from file paths
        $allFiles = $driver->listFiles('media-upload/', true);
        foreach ($allFiles as $filePath) {
            if (preg_match('/^media-upload\/([^\/]+)\//', $filePath, $matches)) {
                $folderPaths[] = $matches[1];
            }
        }
        $folderPaths = array_unique($folderPaths);

        if (empty($folderPaths)) {
            $this->info('No folders found in media-upload storage.');
            return 0;
        }

        $this->info('Found ' . count($folderPaths) . ' folder(s) in storage.');

        // Get all valid folder identifiers from database
        $validFolderIds = MediaUploadFolder::pluck('id')->toArray();
        $validStoragePaths = MediaUploadFolder::whereNotNull('storage_path')
            ->pluck('storage_path')
            ->toArray();

        $orphans = [];
        foreach ($folderPaths as $folderPath) {
            $isValid = false;

            // Check if it's a legacy folder-{id} pattern
            if (preg_match('/^folder-(\d+)$/', $folderPath, $matches)) {
                $folderId = (int) $matches[1];
                if (in_array($folderId, $validFolderIds)) {
                    $isValid = true;
                }
            }

            // Check if it matches a storage_path
            if (in_array($folderPath, $validStoragePaths)) {
                $isValid = true;
            }

            if (!$isValid) {
                $orphans[] = $folderPath;
                $this->line("  <fg=yellow>⚠</> Orphan: media-upload/{$folderPath}");
            } else {
                $this->line("  <fg=green>✓</> Valid: media-upload/{$folderPath}");
            }
        }

        $this->newLine();

        if (empty($orphans)) {
            $this->info('No orphan folders found. Storage is clean.');
            return 0;
        }

        $this->warn('Found ' . count($orphans) . ' orphan folder(s).');

        if ($this->option('dry-run')) {
            $this->newLine();
            $this->warn('DRY RUN - Would delete:');
            foreach ($orphans as $orphan) {
                $this->line("  - media-upload/{$orphan}");
            }
            return 0;
        }

        if (!$this->option('force') && !$this->confirm('Delete ' . count($orphans) . ' orphan folder(s)?')) {
            $this->info('Operation cancelled.');
            return 0;
        }

        $deleted = 0;
        foreach ($orphans as $orphan) {
            $fullPath = 'media-upload/' . $orphan;
            try {
                if ($driver->deleteDirectory($fullPath)) {
                    $deleted++;
                    $this->info("  <fg=green>✓</> Deleted: {$fullPath}");
                } else {
                    $this->error("  <fg=red>✗</> Failed: {$fullPath}");
                }
            } catch (\Exception $e) {
                $this->error("  <fg=red>✗</> Error: {$fullPath} - {$e->getMessage()}");
            }
        }

        $this->newLine();
        $this->info("Deleted {$deleted} of " . count($orphans) . " orphan folder(s).");

        return 0;
    }
}
