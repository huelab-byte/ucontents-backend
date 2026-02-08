<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Encrypted api_secret can exceed VARCHAR(255); use TEXT like api_key.
     */
    public function up(): void
    {
        if (! Schema::hasTable('ai_api_keys')) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE ai_api_keys MODIFY api_secret TEXT NULL COMMENT \'Encrypted API secret if needed\'');
        } elseif ($driver === 'pgsql') {
            DB::statement('ALTER TABLE ai_api_keys ALTER COLUMN api_secret TYPE TEXT');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('ai_api_keys')) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE ai_api_keys MODIFY api_secret VARCHAR(255) NULL COMMENT \'Encrypted API secret if needed\'');
        } elseif ($driver === 'pgsql') {
            DB::statement('ALTER TABLE ai_api_keys ALTER COLUMN api_secret TYPE VARCHAR(255)');
        }
    }
};
