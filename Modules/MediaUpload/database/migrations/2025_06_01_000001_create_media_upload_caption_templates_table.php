<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media_upload_caption_templates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('name');
            $table->string('font')->default('Arial');
            $table->unsignedInteger('font_size')->default(32);
            $table->string('font_color', 20)->default('#FFFFFF');
            $table->string('outline_color', 20)->default('#000000');
            $table->unsignedInteger('outline_size')->default(3);
            $table->string('position', 20)->default('bottom'); // top, center, bottom, instagram
            $table->unsignedInteger('words_per_caption')->default(3);
            $table->boolean('word_highlighting')->default(false);
            $table->string('highlight_color', 20)->nullable();
            $table->string('highlight_style', 20)->default('text'); // text, background
            $table->unsignedTinyInteger('background_opacity')->default(70);
            $table->boolean('enable_alternating_loop')->default(false);
            $table->unsignedInteger('loop_count')->default(1);
            $table->timestamps();

            $table->index(['user_id']);
        });

        if (Schema::hasTable('users')) {
            Schema::table('media_upload_caption_templates', function (Blueprint $table) {
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('media_upload_caption_templates');
    }
};
