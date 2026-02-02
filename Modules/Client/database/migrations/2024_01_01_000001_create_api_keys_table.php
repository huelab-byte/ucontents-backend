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
        if (Schema::hasTable('api_keys')) {
            return;
        }

        Schema::create('api_keys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('api_client_id')->constrained('api_clients')->cascadeOnDelete();
            $table->string('name')->nullable(); // Key name/description
            $table->string('public_key', 64)->unique(); // Format: pk_prod_xxxxx
            $table->text('secret_key'); // Encrypted secret key
            $table->string('key_hash')->index(); // Hashed version for lookup
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('rotated_at')->nullable(); // Track key rotation
            $table->timestamp('revoked_at')->nullable();
            $table->string('revoked_reason')->nullable();
            $table->timestamps();

            $table->index('api_client_id');
            $table->index('is_active');
            $table->index(['public_key', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('api_keys');
    }
};
