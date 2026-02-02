<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media_uploads', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('folder_id');
            $table->unsignedBigInteger('storage_file_id');
            $table->string('title')->nullable();
            $table->string('status', 20)->default('pending'); // pending, processing, ready, failed
            $table->unsignedBigInteger('caption_template_id')->nullable();
            $table->unsignedInteger('loop_count')->default(1);
            $table->boolean('enable_reverse')->default(false);
            $table->string('youtube_heading')->nullable();
            $table->text('social_caption')->nullable();
            $table->json('hashtags')->nullable();
            $table->json('video_metadata')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->foreign('folder_id')->references('id')->on('media_upload_folders')->onDelete('cascade');
            $table->foreign('caption_template_id')->references('id')->on('media_upload_caption_templates')->onDelete('set null');
            $table->index(['user_id']);
            $table->index(['folder_id']);
            $table->index(['status']);
        });

        if (Schema::hasTable('users')) {
            Schema::table('media_uploads', function (Blueprint $table) {
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            });
        }
        if (Schema::hasTable('storage_files')) {
            Schema::table('media_uploads', function (Blueprint $table) {
                $table->foreign('storage_file_id')->references('id')->on('storage_files')->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('media_uploads');
    }
};
