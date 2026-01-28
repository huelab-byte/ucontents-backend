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
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number')->unique();
            $table->unsignedBigInteger('user_id');
            $table->morphs('invoiceable'); // For packages, subscriptions, etc.
            $table->string('type')->default('package'); // package, subscription, one_time
            $table->decimal('subtotal', 15, 2);
            $table->decimal('tax', 15, 2)->default(0);
            $table->decimal('discount', 15, 2)->default(0);
            $table->decimal('total', 15, 2);
            $table->string('currency', 3)->default('USD');
            $table->enum('status', ['draft', 'pending', 'paid', 'overdue', 'cancelled', 'refunded'])->default('draft');
            $table->date('due_date')->nullable();
            $table->date('paid_at')->nullable();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable(); // Additional invoice data
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('invoice_number');
            $table->index('user_id');
            $table->index('status');
            $table->index('type');
            $table->index('due_date');
        });

        // Add foreign keys to users after ensuring users table exists
        if (Schema::hasTable('users')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
                $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
