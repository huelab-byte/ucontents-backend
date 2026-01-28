<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('support_ticket_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('support_ticket_id')
                ->nullable()
                ->constrained('support_tickets')
                ->onDelete('cascade');
            $table->foreignId('support_ticket_reply_id')
                ->nullable()
                ->constrained('support_ticket_replies')
                ->onDelete('cascade');
            $table->unsignedBigInteger('storage_file_id');
            $table->timestamps();

            $table->index('support_ticket_id');
            $table->index('support_ticket_reply_id');
            $table->index('storage_file_id');
        });

        // Add foreign key to storage_files after ensuring storage_files table exists
        if (Schema::hasTable('storage_files')) {
            Schema::table('support_ticket_attachments', function (Blueprint $table) {
                $table->foreign('storage_file_id')->references('id')->on('storage_files')->onDelete('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('support_ticket_attachments');
    }
};
