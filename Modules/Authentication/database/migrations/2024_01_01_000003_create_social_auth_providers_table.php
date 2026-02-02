<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Idempotent: skip if table already exists
        if (Schema::hasTable('social_auth_providers')) {
            return;
        }

        Schema::create('social_auth_providers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('provider'); // google, facebook, tiktok
            $table->string('provider_id'); // External provider user ID
            $table->string('email')->nullable();
            $table->json('provider_data')->nullable(); // Store additional provider data
            $table->timestamps();

            $table->unique(['provider', 'provider_id']);
            $table->index('user_id');
            $table->index('provider');
        });

        // Add foreign key to users after ensuring users table exists
        if (Schema::hasTable('users')) {
            Schema::table('social_auth_providers', function (Blueprint $table) {
                $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('social_auth_providers');
    }
};
