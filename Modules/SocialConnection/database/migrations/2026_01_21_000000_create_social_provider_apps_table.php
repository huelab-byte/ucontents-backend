<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('social_provider_apps', function (Blueprint $table) {
            $table->id();
            $table->string('provider')->unique(); // meta, google, tiktok
            $table->boolean('enabled')->default(false);

            $table->string('client_id')->nullable();
            $table->text('client_secret')->nullable(); // encrypted via model cast

            $table->json('scopes')->nullable();
            $table->json('extra')->nullable();

            // Admin audit (optional)
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->timestamps();

            $table->index(['provider', 'enabled']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('social_provider_apps');
    }
};

