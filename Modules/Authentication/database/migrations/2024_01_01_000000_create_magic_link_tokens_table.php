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
        if (Schema::hasTable('magic_link_tokens')) {
            return;
        }

        Schema::create('magic_link_tokens', function (Blueprint $table) {
            $table->id();
            $table->string('email')->index();
            $table->string('token', 64)->unique();
            $table->timestamp('expires_at');
            $table->boolean('used')->default(false);
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();

            $table->index(['email', 'used']);
            $table->index(['token', 'used', 'expires_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('magic_link_tokens');
    }
};
