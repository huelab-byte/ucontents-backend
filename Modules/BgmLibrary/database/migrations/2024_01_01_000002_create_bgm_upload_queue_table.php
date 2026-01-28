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
        Schema::create('bgm_upload_queue', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('file_name');
            $table->string('file_path'); // Temporary storage path
            $table->unsignedBigInteger('file_size');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('folder_id')->nullable();
            $table->enum('metadata_source', ['title', 'manual'])->default('title');
            $table->enum('status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
            $table->integer('progress')->default(0); // 0-100
            $table->text('error_message')->nullable();
            $table->unsignedBigInteger('bgm_id')->nullable(); // Reference to bgm after processing
            $table->integer('attempts')->default(0);
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
            
            $table->foreign('folder_id')->references('id')->on('bgm_folders')->onDelete('set null');
            $table->foreign('bgm_id')->references('id')->on('bgm')->onDelete('set null');
            
            $table->index(['status', 'created_at']);
            $table->index(['user_id']);
            $table->index(['folder_id']);
        });

        // Add foreign key to users after ensuring users table exists
        if (Schema::hasTable('users')) {
            Schema::table('bgm_upload_queue', function (Blueprint $table) {
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bgm_upload_queue');
    }
};
