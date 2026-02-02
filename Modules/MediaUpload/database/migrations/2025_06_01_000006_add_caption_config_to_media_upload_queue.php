<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('media_upload_queue', function (Blueprint $table) {
            $table->json('caption_config')->nullable()->after('mime_type');
        });
    }

    public function down(): void
    {
        Schema::table('media_upload_queue', function (Blueprint $table) {
            $table->dropColumn('caption_config');
        });
    }
};
