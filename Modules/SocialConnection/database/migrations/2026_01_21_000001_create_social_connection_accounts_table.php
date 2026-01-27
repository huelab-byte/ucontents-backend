<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('social_connection_accounts', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('user_id');
            $table->string('provider'); // meta, google, tiktok
            $table->string('provider_account_id');

            $table->string('email')->nullable();
            $table->string('display_name')->nullable();
            $table->string('avatar_url')->nullable();

            $table->text('access_token')->nullable(); // encrypted via model cast
            $table->text('refresh_token')->nullable(); // encrypted via model cast
            $table->timestamp('expires_at')->nullable();
            $table->json('scopes')->nullable();
            $table->json('raw')->nullable();

            $table->timestamps();

            $table->unique(['provider', 'provider_account_id']);
            // Keep index names short for MySQL identifier limits
            $table->index(['user_id', 'provider'], 'sca_user_provider_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('social_connection_accounts');
    }
};

