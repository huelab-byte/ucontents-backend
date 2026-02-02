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
        if (Schema::hasTable('proxies')) {
            return;
        }

        Schema::create('proxies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('name');
            $table->enum('type', ['http', 'https', 'socks4', 'socks5'])->default('http');
            $table->string('host');
            $table->unsignedInteger('port');
            $table->text('username')->nullable(); // encrypted
            $table->text('password')->nullable(); // encrypted
            $table->boolean('is_enabled')->default(true);
            $table->timestamp('last_checked_at')->nullable();
            $table->enum('last_check_status', ['success', 'failed', 'pending'])->nullable();
            $table->string('last_check_message')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'is_enabled']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('proxies');
    }
};
