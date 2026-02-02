<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media_upload_queue', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('folder_id');
            $table->string('file_name');
            $table->string('file_path');
            $table->unsignedBigInteger('file_size');
            $table->string('mime_type')->nullable();
            $table->string('status', 20)->default('pending'); // pending, processing, completed, failed
            $table->integer('progress')->default(0);
            $table->text('error_message')->nullable();
            $table->unsignedBigInteger('media_upload_id')->nullable();
            $table->integer('attempts')->default(0);
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->foreign('folder_id')->references('id')->on('media_upload_folders')->onDelete('cascade');
            $table->foreign('media_upload_id')->references('id')->on('media_uploads')->onDelete('set null');
            $table->index(['status', 'created_at']);
            $table->index(['user_id']);
            $table->index(['folder_id']);
        });

        if (Schema::hasTable('users')) {
            Schema::table('media_upload_queue', function (Blueprint $table) {
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('media_upload_queue');
    }
};
