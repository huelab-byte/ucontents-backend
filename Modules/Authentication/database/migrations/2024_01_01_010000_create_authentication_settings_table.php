<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('authentication_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique(); // e.g., 'features.magic_link.enabled', 'features.magic_link.token_expiry'
            $table->text('value')->nullable(); // JSON encoded value
            $table->string('type')->default('string'); // string, integer, boolean, array
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // Note: Default settings are seeded via AuthenticationSettingsSeeder
        // Run: php artisan db:seed --class=Modules\\Authentication\\Database\\Seeders\\AuthenticationSettingsSeeder
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('authentication_settings');
    }
};
