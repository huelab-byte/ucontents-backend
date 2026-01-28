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
        Schema::create('ai_prompt_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->text('template')->comment('Prompt template with placeholders');
            $table->json('variables')->nullable()->comment('Available variables for the template');
            $table->string('category')->nullable();
            $table->string('provider_slug')->nullable()->comment('Preferred provider, null for any');
            $table->string('model')->nullable()->comment('Preferred model, null for default');
            $table->json('settings')->nullable()->comment('Default model settings (temperature, max_tokens, etc.)');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_system')->default(false)->comment('System templates cannot be deleted');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['is_active', 'category']);
            $table->index('provider_slug');
        });

        // Add foreign key to users after ensuring users table exists
        if (Schema::hasTable('users')) {
            Schema::table('ai_prompt_templates', function (Blueprint $table) {
                $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_prompt_templates');
    }
};
