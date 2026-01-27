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
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->string('subscription_number')->unique();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->morphs('subscriptionable'); // For packages, services, etc.
            $table->string('name'); // Subscription name
            $table->enum('interval', ['weekly', 'monthly', 'yearly']);
            $table->decimal('amount', 15, 2);
            $table->string('currency', 3)->default('USD');
            $table->enum('status', ['active', 'paused', 'cancelled', 'expired', 'pending'])->default('pending');
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->date('next_billing_date')->nullable();
            $table->date('last_payment_date')->nullable();
            $table->foreignId('payment_gateway_id')->nullable()->constrained('payment_gateways')->nullOnDelete();
            $table->string('gateway_subscription_id')->nullable(); // External gateway subscription ID
            $table->json('gateway_data')->nullable(); // Additional gateway data
            $table->json('metadata')->nullable(); // Additional subscription data
            $table->timestamps();
            $table->softDeletes();

            $table->index('subscription_number');
            $table->index('user_id');
            $table->index('status');
            $table->index('interval');
            $table->index('next_billing_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
