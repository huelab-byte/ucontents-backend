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

        $connection = Schema::getConnection()->getDriverName();

        // `token_context` is cast as `encrypted:array` on the model, which means
        // Laravel will store an encrypted string, not valid JSON. To avoid JSON
        // constraint failures (MySQL error 4025), we need a plain TEXT column.
        if ($connection === 'mysql') {
            DB::statement('ALTER TABLE `social_connection_channels` MODIFY `token_context` TEXT NULL');
        } elseif ($connection === 'pgsql') {
            DB::statement('ALTER TABLE social_connection_channels ALTER COLUMN token_context TYPE TEXT');
        } else {
            Schema::table('social_connection_channels', function (Blueprint $table): void {
                $table->text('token_context')->nullable()->change();
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('social_connection_channels')) {
            return;
        }

        $connection = Schema::getConnection()->getDriverName();

        // Best-effort revert back to JSON for drivers that support it.
        if ($connection === 'mysql') {
            DB::statement('ALTER TABLE `social_connection_channels` MODIFY `token_context` JSON NULL');
        } elseif ($connection === 'pgsql') {
            DB::statement('ALTER TABLE social_connection_channels ALTER COLUMN token_context TYPE JSON USING token_context::json');
        } else {
            Schema::table('social_connection_channels', function (Blueprint $table): void {
                $table->json('token_context')->nullable()->change();
            });
        }
    }
};

