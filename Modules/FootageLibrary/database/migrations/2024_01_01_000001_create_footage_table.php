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
        Schema::create('footage', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('storage_file_id');
            $table->unsignedBigInteger('folder_id')->nullable();
            $table->string('title');
            $table->json('metadata'); // Structured metadata JSON
            $table->string('embedding_id')->nullable(); // Qdrant point ID
            $table->unsignedBigInteger('user_id');
            $table->enum('status', ['pending', 'processing', 'ready', 'failed'])->default('pending');
            $table->timestamps();
            $table->softDeletes();
            
            $table->foreign('storage_file_id')->references('id')->on('storage_files')->onDelete('cascade');
            $table->foreign('folder_id')->references('id')->on('footage_folders')->onDelete('set null');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            
            $table->index(['user_id']);
            $table->index(['folder_id']);
            $table->index(['status']);
            $table->index(['embedding_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('footage');
    }
};
