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
        Schema::create('support_tickets', function (Blueprint $table) {
            $table->id();
            $table->string('ticket_number', 50)->unique();
            $table->unsignedBigInteger('user_id');
            $table->string('subject', 255);
            $table->text('description');
            $table->enum('status', ['open', 'in_progress', 'resolved', 'closed'])->default('open');
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('low');
            $table->string('category', 100)->nullable();
            $table->unsignedBigInteger('assigned_to_user_id')->nullable();
            $table->timestamp('last_replied_at')->nullable();
            $table->unsignedBigInteger('last_replied_by_user_id')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('user_id');
            $table->index('status');
            $table->index('assigned_to_user_id');
            $table->index('created_at');
        });

        // Add foreign keys to users after ensuring users table exists
        if (Schema::hasTable('users')) {
            Schema::table('support_tickets', function (Blueprint $table) {
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
                $table->foreign('assigned_to_user_id')->references('id')->on('users')->nullOnDelete();
                $table->foreign('last_replied_by_user_id')->references('id')->on('users')->nullOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('support_tickets');
    }
};
