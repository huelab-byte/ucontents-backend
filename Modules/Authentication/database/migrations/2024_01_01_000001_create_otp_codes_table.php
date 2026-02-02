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
        if (Schema::hasTable('otp_codes')) {
            return;
        }

        Schema::create('otp_codes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('code', 6); // 6-digit OTP
            $table->string('type')->default('login'); // login, verification, password_reset
            $table->timestamp('expires_at');
            $table->boolean('used')->default(false);
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'type', 'used']);
            $table->index(['code', 'used', 'expires_at']);
        });

        // Add foreign key to users after ensuring users table exists
        if (Schema::hasTable('users')) {
            Schema::table('otp_codes', function (Blueprint $table) {
                $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('otp_codes');
    }
};
