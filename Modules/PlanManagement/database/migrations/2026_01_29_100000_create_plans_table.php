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
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->string('slug', 100)->unique();
            $table->text('description')->nullable();
            $table->unsignedInteger('ai_usage_limit')->nullable();
            $table->unsignedInteger('max_file_upload')->default(0);
            $table->unsignedBigInteger('total_storage_bytes')->default(0);
            $table->json('features')->nullable();
            $table->unsignedInteger('max_connections')->default(0);
            $table->unsignedInteger('monthly_post_limit')->nullable();
            $table->enum('subscription_type', ['weekly', 'monthly', 'yearly', 'lifetime']);
            $table->decimal('price', 15, 2)->default(0);
            $table->string('currency', 3)->default('USD');
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index('is_active');
            $table->index('subscription_type');
            $table->index('sort_order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
