<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Adds featured, free plan, and trial fields (inspired by sentosh-smm plan system).
     */
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->boolean('featured')->default(false)->after('sort_order');
            $table->boolean('is_free_plan')->default(false)->after('featured');
            $table->unsignedInteger('trial_days')->nullable()->after('is_free_plan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn(['featured', 'is_free_plan', 'trial_days']);
        });
    }
};
