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
        Schema::create('ai_usage_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('api_key_id')->constrained('ai_api_keys')->onDelete('cascade');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('provider_slug');
            $table->string('model');
            $table->text('prompt')->nullable();
            $table->text('response')->nullable();
            $table->integer('prompt_tokens')->default(0);
            $table->integer('completion_tokens')->default(0);
            $table->integer('total_tokens')->default(0);
            $table->decimal('cost', 10, 6)->nullable()->comment('Cost in USD');
            $table->integer('response_time_ms')->nullable()->comment('Response time in milliseconds');
            $table->string('status')->default('success')->comment('success, error, rate_limited');
            $table->text('error_message')->nullable();
            $table->string('module')->nullable()->comment('Which module made the call');
            $table->string('feature')->nullable()->comment('Which feature used the AI');
            $table->json('metadata')->nullable()->comment('Additional metadata');
            $table->timestamps();
            
            $table->index(['api_key_id', 'created_at']);
            $table->index(['user_id', 'created_at']);
            $table->index(['provider_slug', 'created_at']);
            $table->index('status');
        });

        // Add foreign key to users after ensuring users table exists
        if (Schema::hasTable('users')) {
            Schema::table('ai_usage_logs', function (Blueprint $table) {
                $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_usage_logs');
    }
};
