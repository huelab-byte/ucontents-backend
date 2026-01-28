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
        Schema::create('refunds', function (Blueprint $table) {
            $table->id();
            $table->string('refund_number')->unique();
            $table->foreignId('payment_id')->constrained('payments')->cascadeOnDelete();
            $table->foreignId('invoice_id')->nullable()->constrained('invoices')->nullOnDelete();
            $table->unsignedBigInteger('user_id');
            $table->foreignId('payment_gateway_id')->nullable()->constrained('payment_gateways')->nullOnDelete();
            $table->decimal('amount', 15, 2);
            $table->string('currency', 3)->default('USD');
            $table->enum('status', ['pending', 'processing', 'completed', 'failed', 'cancelled'])->default('pending');
            $table->string('gateway_refund_id')->nullable(); // External gateway refund ID
            $table->text('reason')->nullable();
            $table->json('gateway_response')->nullable(); // Full response from gateway
            $table->json('metadata')->nullable(); // Additional refund data
            $table->timestamp('processed_at')->nullable();
            $table->text('failure_reason')->nullable();
            $table->unsignedBigInteger('processed_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('refund_number');
            $table->index('payment_id');
            $table->index('invoice_id');
            $table->index('user_id');
            $table->index('status');
            $table->index('gateway_refund_id');
        });

        // Add foreign keys to users after ensuring users table exists
        if (Schema::hasTable('users')) {
            Schema::table('refunds', function (Blueprint $table) {
                $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
                $table->foreign('processed_by')->references('id')->on('users')->nullOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('refunds');
    }
};
