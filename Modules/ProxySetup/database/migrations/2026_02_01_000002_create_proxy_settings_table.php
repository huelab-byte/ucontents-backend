<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Idempotent: skip if table already exists
        if (Schema::hasTable('proxy_settings')) {
            return;
        }

        Schema::create('proxy_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->onDelete('cascade');
            $table->boolean('use_random_proxy')->default(false);
            $table->boolean('apply_to_all_channels')->default(true);
            $table->enum('on_proxy_failure', ['stop_automation', 'continue_without_proxy'])->default('continue_without_proxy');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('proxy_settings');
    }
};
