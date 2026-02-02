<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Migration to add storage_path column to media_upload_folders table.
 * 
 * This migration is safe to run on production:
 * - Uses raw DB queries instead of Eloquent to avoid model dependencies
 * - Column is nullable with default to prevent data issues
 * - Populates existing records using DB facade
 * - No data loss - existing folders get storage_path based on their name
 */
return new class extends Migration
{
    public function up(): void
    {
        // Skip if column already exists (idempotent)
        if (Schema::hasColumn('media_upload_folders', 'storage_path')) {
            return;
        }

        // Step 1: Add nullable column with default
        Schema::table('media_upload_folders', function (Blueprint $table) {
            $table->string('storage_path')->nullable()->after('name');
        });

        // Step 2: Populate storage_path for existing folders using raw DB queries
        // This avoids Eloquent model dependencies during migration
        $folders = DB::table('media_upload_folders')
            ->whereNull('storage_path')
            ->get(['id', 'name', 'user_id', 'parent_id']);

        foreach ($folders as $folder) {
            $basePath = $this->generateStoragePath($folder->name);
            $storagePath = $basePath;
            $counter = 1;

            // Ensure uniqueness per user AND parent (same name allowed in different parents)
            while (DB::table('media_upload_folders')
                ->where('user_id', $folder->user_id)
                ->where('parent_id', $folder->parent_id)
                ->where('storage_path', $storagePath)
                ->where('id', '!=', $folder->id)
                ->exists()) {
                $storagePath = $basePath . '-' . $counter;
                $counter++;
            }

            DB::table('media_upload_folders')
                ->where('id', $folder->id)
                ->update(['storage_path' => $storagePath]);
        }

        // Step 3: Add index for performance (not unique - allows same storage_path in different parents)
        Schema::table('media_upload_folders', function (Blueprint $table) {
            $table->index(['user_id', 'parent_id', 'storage_path'], 'media_upload_folders_user_parent_storage_idx');
        });
    }

    public function down(): void
    {
        if (!Schema::hasColumn('media_upload_folders', 'storage_path')) {
            return;
        }

        Schema::table('media_upload_folders', function (Blueprint $table) {
            // Check if index exists before dropping
            $indexName = 'media_upload_folders_user_parent_storage_idx';
            $indexes = DB::select("SHOW INDEX FROM media_upload_folders WHERE Key_name = ?", [$indexName]);
            if (!empty($indexes)) {
                $table->dropIndex($indexName);
            }
            $table->dropColumn('storage_path');
        });
    }

    /**
     * Generate a storage-safe path from folder name
     */
    private function generateStoragePath(string $name): string
    {
        // Sanitize the folder name for storage use
        $sanitized = Str::slug($name, '-');

        // Ensure it's not empty
        if (empty($sanitized)) {
            $sanitized = 'folder-' . uniqid();
        }

        // Limit length to prevent path issues (50 chars max)
        if (strlen($sanitized) > 50) {
            $sanitized = substr($sanitized, 0, 50);
        }

        return $sanitized;
    }
};
