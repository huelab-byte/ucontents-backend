<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('social_connection_channels')) {
            return;
        }

        Schema::table('social_connection_channels', function (Blueprint $table): void {
            $table->unsignedBigInteger('group_id')->nullable()->after('user_id');
            $table->index(['user_id', 'group_id']);
            $table->foreign('group_id')
                ->references('id')
                ->on('social_connection_groups')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('social_connection_channels')) {
            return;
        }

        Schema::table('social_connection_channels', function (Blueprint $table): void {
            $table->dropForeign(['group_id']);
            $table->dropIndex(['user_id', 'group_id']);
            $table->dropColumn('group_id');
        });
    }
};
