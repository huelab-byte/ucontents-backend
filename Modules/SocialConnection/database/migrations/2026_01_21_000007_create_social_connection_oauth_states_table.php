<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('social_connection_oauth_states', function (Blueprint $table) {
            $table->id();
            $table->string('provider', 32);
            $table->string('state', 64)->unique();
            $table->json('payload');
            $table->timestamp('expires_at');
            $table->timestamps();

            $table->index(['provider', 'state']);
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('social_connection_oauth_states');
    }
};
