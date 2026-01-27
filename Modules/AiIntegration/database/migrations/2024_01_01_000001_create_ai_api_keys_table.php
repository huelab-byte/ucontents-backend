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
        Schema::create('ai_api_keys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('provider_id')->constrained('ai_providers')->onDelete('cascade');
            $table->string('name'); // Friendly name for the API key
            $table->text('api_key')->comment('Encrypted API key');
            $table->string('api_secret')->nullable()->comment('Encrypted API secret if needed');
            $table->string('endpoint_url')->nullable()->comment('Custom endpoint URL (for Azure OpenAI, etc.)');
            $table->string('organization_id')->nullable()->comment('Organization ID (for OpenAI)');
            $table->string('project_id')->nullable()->comment('Project ID (for some providers)');
            $table->boolean('is_active')->default(true);
            $table->integer('priority')->default(0)->comment('Higher priority keys are preferred');
            $table->integer('rate_limit_per_minute')->nullable();
            $table->integer('rate_limit_per_day')->nullable();
            $table->json('metadata')->nullable()->comment('Additional metadata');
            $table->timestamp('last_used_at')->nullable();
            $table->bigInteger('total_requests')->default(0);
            $table->bigInteger('total_tokens')->default(0);
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['provider_id', 'is_active']);
            $table->index('priority');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_api_keys');
    }
};
