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
        Schema::create('storage_files', function (Blueprint $table) {
            $table->id();
            $table->string('driver')->default('local'); // Which storage driver was used
            $table->string('path'); // File path in storage
            $table->string('original_name'); // Original filename
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size'); // File size in bytes
            $table->string('disk')->nullable(); // Laravel disk name
            $table->string('url')->nullable(); // Public URL if available
            $table->unsignedBigInteger('user_id')->nullable(); // Who uploaded it
            $table->string('reference_type')->nullable(); // Polymorphic relation type
            $table->unsignedBigInteger('reference_id')->nullable(); // Polymorphic relation ID
            $table->boolean('is_used')->default(true); // Whether file is actively used
            $table->timestamp('last_accessed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['driver', 'path']);
            $table->index(['user_id']);
            $table->index(['reference_type', 'reference_id']);
            $table->index(['is_used', 'last_accessed_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('storage_files');
    }
};
