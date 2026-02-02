<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('bulk_posting_campaign_logs')) {
            return;
        }

        Schema::create('bulk_posting_campaign_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('bulk_posting_campaign_id');
            $table->unsignedBigInteger('bulk_posting_content_item_id')->nullable();
            $table->string('event_type', 64); // post_scheduled, post_published, post_failed, repost_scheduled, etc.
            $table->json('payload')->nullable(); // channel_id, external_post_id, error_message etc.
            $table->timestamps();

            $table->foreign('bulk_posting_campaign_id', 'bp_log_campaign_fk')
                ->references('id')
                ->on('bulk_posting_campaigns')
                ->onDelete('cascade');
            $table->foreign('bulk_posting_content_item_id', 'bp_log_content_fk')
                ->references('id')
                ->on('bulk_posting_content_items')
                ->onDelete('set null');
            $table->index(['bulk_posting_campaign_id', 'created_at'], 'bp_log_campaign_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bulk_posting_campaign_logs');
    }
};
