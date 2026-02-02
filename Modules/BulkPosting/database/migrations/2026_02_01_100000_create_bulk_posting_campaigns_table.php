<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('bulk_posting_campaigns')) {
            return;
        }

        Schema::create('bulk_posting_campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('brand_name');
            $table->string('project_name');
            if (Schema::hasTable('storage_files')) {
                $table->foreignId('brand_logo_storage_file_id')->nullable()->constrained('storage_files')->onDelete('set null');
            } else {
                $table->unsignedBigInteger('brand_logo_storage_file_id')->nullable();
            }
            $table->string('content_source_type', 32); // csv_file | media_upload
            $table->json('content_source_config')->nullable(); // {"folder_ids": [1,2]} or {"csv_storage_file_id": 123}
            $table->string('schedule_condition', 32); // minute | hourly | daily | weekly | monthly
            $table->unsignedInteger('schedule_interval')->default(1);
            $table->boolean('repost_enabled')->default(false);
            $table->string('repost_condition', 32)->nullable(); // minute | hourly | daily | weekly | monthly
            $table->unsignedInteger('repost_interval')->nullable()->default(0);
            $table->unsignedInteger('repost_max_count')->nullable()->default(1);
            $table->string('status', 32)->default('draft'); // draft | running | paused | completed | failed
            $table->timestamp('started_at')->nullable();
            $table->timestamp('paused_at')->nullable();
            $table->timestamp('last_post_at')->nullable();
            $table->timestamp('last_repost_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bulk_posting_campaigns');
    }
};
