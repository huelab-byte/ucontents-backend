<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('social_connection_channels')) {
            return;
        }

        // Use raw SQL to avoid requiring doctrine/dbal for column changes.
        // Store full provider avatar URLs without truncation.
        $connection = Schema::getConnection()->getDriverName();

        if ($connection === 'mysql') {
            DB::statement('ALTER TABLE `social_connection_channels` MODIFY `avatar_url` TEXT NULL');
        } elseif ($connection === 'pgsql') {
            DB::statement('ALTER TABLE social_connection_channels ALTER COLUMN avatar_url TYPE TEXT');
        } else {
            // Fallback: best-effort using Schema builder where supported.
            Schema::table('social_connection_channels', function (Blueprint $table): void {
                $table->text('avatar_url')->nullable()->change();
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('social_connection_channels')) {
            return;
        }

        $connection = Schema::getConnection()->getDriverName();

        if ($connection === 'mysql') {
            DB::statement('ALTER TABLE `social_connection_channels` MODIFY `avatar_url` VARCHAR(255) NULL');
        } elseif ($connection === 'pgsql') {
            DB::statement('ALTER TABLE social_connection_channels ALTER COLUMN avatar_url TYPE VARCHAR(255)');
        } else {
            Schema::table('social_connection_channels', function (Blueprint $table): void {
                $table->string('avatar_url', 255)->nullable()->change();
            });
        }
    }
};

