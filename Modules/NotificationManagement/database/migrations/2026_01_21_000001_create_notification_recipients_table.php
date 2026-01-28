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
        Schema::create('notification_recipients', function (Blueprint $table) {
            $table->id();

            $table->foreignId('notification_id')
                ->constrained('notifications')
                ->cascadeOnDelete();

            $table->unsignedBigInteger('user_id');

            $table->timestamp('read_at')->nullable();
            $table->timestamp('delivered_email_at')->nullable();

            $table->timestamps();

            $table->unique(['notification_id', 'user_id']);
            $table->index(['user_id', 'read_at']);
        });

        // Add foreign key to users after ensuring users table exists
        if (Schema::hasTable('users')) {
            Schema::table('notification_recipients', function (Blueprint $table) {
                $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_recipients');
    }
};

