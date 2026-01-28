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
        Schema::create('image_overlays', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('storage_file_id');
            $table->unsignedBigInteger('folder_id')->nullable();
            $table->string('title')->nullable();
            $table->json('metadata')->nullable();
            $table->unsignedBigInteger('user_id');
            $table->enum('status', ['pending', 'processing', 'ready', 'failed'])->default('ready');
            $table->timestamps();
            $table->softDeletes();
            
            $table->foreign('storage_file_id')->references('id')->on('storage_files')->onDelete('cascade');
            $table->foreign('folder_id')->references('id')->on('image_overlay_folders')->onDelete('set null');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            
            $table->index(['user_id', 'status']);
            $table->index(['folder_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('image_overlays');
    }
};
