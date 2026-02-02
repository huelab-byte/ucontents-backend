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
        // First convert string values to numeric strings
        $rows = DB::table('media_upload_content_settings')->get();
        foreach ($rows as $row) {
            $hl = is_numeric($row->heading_length)
                ? (int) $row->heading_length
                : match ($row->heading_length) {
                    'short' => 40,
                    'long' => 90,
                    default => 60,
                };
            $cl = is_numeric($row->caption_length ?? '')
                ? (int) $row->caption_length
                : match ($row->caption_length ?? 'medium') {
                    'short' => 125,
                    'long' => 450,
                    default => 250,
                };
            DB::table('media_upload_content_settings')
                ->where('id', $row->id)
                ->update(['heading_length' => (string) $hl, 'caption_length' => (string) $cl]);
        }

        Schema::table('media_upload_content_settings', function (Blueprint $table) {
            $table->unsignedInteger('heading_length')->default(60)->change();
            $table->unsignedInteger('caption_length')->default(250)->change();
            $table->unsignedTinyInteger('hashtag_count')->default(3)->change();
        });
    }

    public function down(): void
    {
        Schema::table('media_upload_content_settings', function (Blueprint $table) {
            $table->string('heading_length', 20)->default('medium')->change();
            $table->string('caption_length', 20)->default('medium')->change();
            $table->unsignedTinyInteger('hashtag_count')->default(10)->change();
        });
    }
};
