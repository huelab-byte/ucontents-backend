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
        if (Schema::hasTable('proxy_channel_assignments')) {
            return;
        }

        Schema::create('proxy_channel_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proxy_id')->constrained('proxies')->onDelete('cascade');
            $table->foreignId('social_connection_channel_id')->constrained('social_connection_channels')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['proxy_id', 'social_connection_channel_id'], 'proxy_channel_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('proxy_channel_assignments');
    }
};
