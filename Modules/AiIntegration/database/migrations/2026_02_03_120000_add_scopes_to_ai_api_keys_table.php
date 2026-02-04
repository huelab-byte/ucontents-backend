<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add scopes column to ai_api_keys table for scope-based API key selection.
 * 
 * Scopes allow configuring which API keys are used for specific AI tasks.
 * Example: API key 1 for vision tasks, API key 2 for text generation.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Idempotent: skip if column already exists
        if (Schema::hasColumn('ai_api_keys', 'scopes')) {
            return;
        }

        Schema::table('ai_api_keys', function (Blueprint $table) {
            // JSON array of scope strings that this API key can be used for
            // If null or empty, the key can be used for any scope (backward compatible)
            $table->json('scopes')->nullable()->after('metadata')
                ->comment('Array of scopes this API key can be used for. Empty = all scopes.');
        });
    }

    public function down(): void
    {
        if (!Schema::hasColumn('ai_api_keys', 'scopes')) {
            return;
        }

        Schema::table('ai_api_keys', function (Blueprint $table) {
            $table->dropColumn('scopes');
        });
    }
};
