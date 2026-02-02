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
        if (! Schema::hasTable('bulk_posting_campaign_connections')) {
            Schema::create('bulk_posting_campaign_connections', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('bulk_posting_campaign_id');
            $table->string('connection_type', 16); // channel | group
            $table->unsignedBigInteger('connection_id'); // social_connection_channel_id or social_connection_group_id
            $table->timestamps();

            $table->foreign('bulk_posting_campaign_id', 'bp_conn_campaign_fk')
                ->references('id')
                ->on('bulk_posting_campaigns')
                ->onDelete('cascade');
            $table->unique(['bulk_posting_campaign_id', 'connection_type', 'connection_id'], 'bp_campaign_conn_unique');
            });
        } else {
            // Table exists from failed migration - add FK with short name if missing
            $indexes = DB::select("SHOW INDEX FROM bulk_posting_campaign_connections WHERE Key_name = 'bp_conn_campaign_fk'");
            if (empty($indexes)) {
                Schema::table('bulk_posting_campaign_connections', function (Blueprint $table) {
                    $table->foreign('bulk_posting_campaign_id', 'bp_conn_campaign_fk')
                        ->references('id')
                        ->on('bulk_posting_campaigns')
                        ->onDelete('cascade');
                });
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('bulk_posting_campaign_connections');
    }
};
