<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('media_upload_caption_templates', function (Blueprint $table) {
            $table->string('font_weight', 20)->default('regular')->after('font_size');
        });
    }

    public function down(): void
    {
        Schema::table('media_upload_caption_templates', function (Blueprint $table) {
            $table->dropColumn('font_weight');
        });
    }
};
