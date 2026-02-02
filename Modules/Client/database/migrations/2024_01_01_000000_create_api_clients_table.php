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
        if (Schema::hasTable('api_clients')) {
            return;
        }

        Schema::create('api_clients', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('environment')->default('production'); // production, staging, development
            $table->boolean('is_active')->default(true);
            $table->json('allowed_endpoints')->nullable(); // Scoped endpoints
            $table->json('rate_limit')->nullable(); // Custom rate limit override
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('is_active');
            $table->index('environment');
            $table->index('created_by');
        });

        // Add foreign key constraint after table creation (ensures users table exists)
        if (Schema::hasTable('users')) {
            Schema::table('api_clients', function (Blueprint $table) {
                $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('api_clients');
    }
};
