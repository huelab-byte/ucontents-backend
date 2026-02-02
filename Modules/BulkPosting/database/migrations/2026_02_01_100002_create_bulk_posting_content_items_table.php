<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('bulk_posting_content_items')) {
            Schema::create('bulk_posting_content_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('bulk_posting_campaign_id');
            $table->string('source_type', 32); // csv | media_upload
            $table->string('source_ref', 255); // media_upload_id or csv row index
            $table->json('payload')->nullable(); // caption, media_urls, hashtags etc.
            $table->string('status', 32)->default('pending'); // pending | scheduled | published | failed | skipped
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->unsignedInteger('republish_count')->default(0);
            $table->text('error_message')->nullable();
            $table->json('external_post_ids')->nullable(); // channel_id => provider post ID
            $table->timestamps();

            $table->foreign('bulk_posting_campaign_id', 'bp_content_campaign_fk')
                ->references('id')
                ->on('bulk_posting_campaigns')
                ->onDelete('cascade');
            $table->index(['bulk_posting_campaign_id', 'status'], 'bp_content_status_idx');
            $table->index(['bulk_posting_campaign_id', 'published_at'], 'bp_content_pub_idx');
            });
        } else {
            // Table exists from failed migration - add missing indexes if needed
            $indexes = collect(DB::select("SHOW INDEX FROM bulk_posting_content_items WHERE Key_name = 'bp_content_pub_idx'"));
            if ($indexes->isEmpty()) {
                Schema::table('bulk_posting_content_items', function (Blueprint $table) {
                    $table->index(['bulk_posting_campaign_id', 'published_at'], 'bp_content_pub_idx');
                });
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('bulk_posting_content_items');
    }
};
