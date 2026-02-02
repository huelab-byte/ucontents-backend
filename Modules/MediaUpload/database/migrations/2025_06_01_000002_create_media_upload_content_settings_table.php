<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media_upload_content_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('folder_id')->unique();
            $table->string('content_source_type', 20); // prompt, frames, title
            $table->unsignedBigInteger('ai_prompt_template_id')->nullable();
            $table->text('custom_prompt')->nullable();
            $table->string('heading_length', 20)->default('medium'); // short, medium, long
            $table->boolean('heading_emoji')->default(false);
            $table->string('caption_length', 20)->default('medium'); // short, medium, long
            $table->unsignedTinyInteger('hashtag_count')->default(10);
            $table->timestamps();

            $table->foreign('folder_id')->references('id')->on('media_upload_folders')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_upload_content_settings');
    }
};
