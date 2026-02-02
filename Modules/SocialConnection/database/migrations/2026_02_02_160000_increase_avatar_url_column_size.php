<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Increase avatar_url column size from VARCHAR(255) to TEXT.
 * 
 * TikTok avatar URLs can be 300+ characters due to signed URL parameters,
 * which exceeds the default VARCHAR(255) limit causing "Data too long" errors.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Update social_connection_accounts table
        Schema::table('social_connection_accounts', function (Blueprint $table) {
            $table->text('avatar_url')->nullable()->change();
        });

        // Also update social_connection_channels table if it has avatar_url
        if (Schema::hasColumn('social_connection_channels', 'avatar_url')) {
            Schema::table('social_connection_channels', function (Blueprint $table) {
                $table->text('avatar_url')->nullable()->change();
            });
        }
    }

    public function down(): void
    {
        Schema::table('social_connection_accounts', function (Blueprint $table) {
            $table->string('avatar_url')->nullable()->change();
        });

        if (Schema::hasColumn('social_connection_channels', 'avatar_url')) {
            Schema::table('social_connection_channels', function (Blueprint $table) {
                $table->string('avatar_url')->nullable()->change();
            });
        }
    }
};
