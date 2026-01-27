<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('social_connection_channels', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('social_connection_account_id')->nullable();

            $table->string('provider'); // meta, google, tiktok
            $table->string('type'); // facebook_page, instagram_business, youtube_channel, tiktok_profile
            $table->string('provider_channel_id');

            $table->string('name');
            $table->string('username')->nullable();
            $table->string('avatar_url')->nullable();

            $table->boolean('is_active')->default(true);

            // Provider-specific metadata (IDs, handles, etc.)
            $table->json('metadata')->nullable();

            // Provider-specific token context (e.g. page token). Encrypted via model cast.
            $table->json('token_context')->nullable();

            // Future package/entitlement association (design only; enforcement later)
            $table->unsignedBigInteger('connected_via_package_id')->nullable();
            $table->json('labels')->nullable();

            $table->timestamps();

            $table->unique(['provider', 'type', 'provider_channel_id'], 'social_channels_unique_provider_type_id');
            $table->index(['user_id', 'provider', 'type']);
            $table->index(['user_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('social_connection_channels');
    }
};

