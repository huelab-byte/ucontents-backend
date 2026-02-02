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
        if (Schema::hasTable('api_key_activity_logs')) {
            return;
        }

        Schema::create('api_key_activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('api_key_id')->constrained('api_keys')->cascadeOnDelete();
            $table->foreignId('api_client_id')->constrained('api_clients')->cascadeOnDelete();
            $table->string('endpoint');
            $table->string('method', 10); // GET, POST, etc.
            $table->integer('status_code')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->json('request_data')->nullable();
            $table->json('response_data')->nullable();
            $table->integer('response_time_ms')->nullable();
            $table->timestamps();

            $table->index('api_key_id');
            $table->index('api_client_id');
            $table->index('created_at');
            $table->index(['api_key_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('api_key_activity_logs');
    }
};
