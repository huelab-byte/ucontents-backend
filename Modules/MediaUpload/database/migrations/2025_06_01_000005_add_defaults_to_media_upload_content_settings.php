<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('media_upload_content_settings', function (Blueprint $table) {
            $table->unsignedBigInteger('default_caption_template_id')->nullable()->after('hashtag_count');
            $table->unsignedInteger('default_loop_count')->default(1)->after('default_caption_template_id');
            $table->boolean('default_enable_reverse')->default(false)->after('default_loop_count');
        });

        Schema::table('media_upload_content_settings', function (Blueprint $table) {
            $table->foreign('default_caption_template_id', 'mu_content_settings_caption_tpl_id_foreign')
                ->references('id')
                ->on('media_upload_caption_templates')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('media_upload_content_settings', function (Blueprint $table) {
            $table->dropForeign('mu_content_settings_caption_tpl_id_foreign');
            $table->dropColumn(['default_caption_template_id', 'default_loop_count', 'default_enable_reverse']);
        });
    }
};
