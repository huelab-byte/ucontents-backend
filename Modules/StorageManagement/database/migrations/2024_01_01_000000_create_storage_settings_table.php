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
        Schema::create('storage_settings', function (Blueprint $table) {
            $table->id();
            $table->string('driver')->default('local'); // local, do_s3, aws_s3, contabo_s3, cloudflare_r2, backblaze_b2
            $table->boolean('is_active')->default(true);
            
            // S3 Configuration (for DO S3, AWS S3, Contabo S3)
            $table->string('key')->nullable(); // Access Key ID
            $table->string('secret')->nullable(); // Secret Access Key
            $table->string('region')->nullable(); // Region
            $table->string('bucket')->nullable(); // Bucket name
            $table->string('endpoint')->nullable(); // Custom endpoint (for DO, Contabo)
            $table->string('url')->nullable(); // CDN URL (optional)
            $table->boolean('use_path_style_endpoint')->default(false); // For S3-compatible services
            
            // Local Storage Configuration
            $table->string('root_path')->nullable(); // For local storage
            
            // Additional settings
            $table->json('metadata')->nullable(); // Additional driver-specific settings
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('storage_settings');
    }
};
