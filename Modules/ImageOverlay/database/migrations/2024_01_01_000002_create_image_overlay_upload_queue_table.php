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
        Schema::create('image_overlay_upload_queue', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('file_name');
            $table->string('file_path'); // Temporary storage path
            $table->unsignedBigInteger('file_size');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('folder_id')->nullable();
            $table->enum('status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
            $table->integer('progress')->default(0); // 0-100
            $table->text('error_message')->nullable();
            $table->unsignedBigInteger('image_overlay_id')->nullable(); // Reference to image_overlay after processing
            $table->integer('attempts')->default(0);
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
            
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('folder_id')->references('id')->on('image_overlay_folders')->onDelete('set null');
            $table->foreign('image_overlay_id')->references('id')->on('image_overlays')->onDelete('set null');
            
            $table->index(['status', 'created_at']);
            $table->index(['user_id']);
            $table->index(['folder_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('image_overlay_upload_queue');
    }
};
